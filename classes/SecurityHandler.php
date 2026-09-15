<?php

namespace Vanderbilt\HarmonistHubPublicExternalModule;

use REDCap;

class SecurityHandler
{
    const SESSION_TOKEN_STRING = 'token';
    const SESSION_OPTION_STRING = 'option';
    const SESSION_OUT_STRING = 'sout';
    const SESSION_COOKIE_STRING = 'harmonist_token_payload';
    private $credentialsPath;
    private $module;
    private $projectId;
    private $cookieKeyCrypt;
    private $pidsArray = [];
    private $isAuthorized = false;
    private $hubName;
    private $token;
    private $settings;
    private $tokenSessionName;
    private $requestToken;
    private $requestOption;
    private $hasNoauth;
    private $requestUrl;

    public function __construct(HarmonistHubPublicExternalModule $module, $projectId)
    {
        $this->module = $module;
        $this->projectId = $projectId;
        $this->pidsArray = self::getPidsArray();
        $this->settings = self::getSettingsData();
        $this->tokenSessionName = $this->hubName . $this->pidsArray['PROJECTS'];
        $this->cookieKeyCrypt = $module->getProjectSetting('cookie-key', $projectId);
        if (empty($this->cookieKeyCrypt)) {
            $this->cookieKeyCrypt = self::generateHexKey();
            $module->setProjectSetting('cookie-key', $this->cookieKeyCrypt, $projectId);
        }

        // Check credentials path
        // Preserve a leading slash (absolute path) and ensure a single trailing slash
        $rawPath = $this->module->getProjectSetting('credentials-path', $projectId);
        if (empty($rawPath)) {
            $rawPath = $this->module->getProjectSetting('credentials-path', $this->getPidsArray()['PROJECTS']);
        }

        $this->credentialsPath = $this->normalizeCredentialsPath($rawPath);
    }

    public function getProjectId(): int
    {
        return $this->projectId;
    }

    public function setRequestToken($requestToken): void
    {
        $this->requestToken = $requestToken;
    }

    public function getRequestUrl(): array
    {
        return $this->requestUrl;
    }

    public function setRequestUrl($requestUrl): void
    {
        $this->requestUrl = $requestUrl;
        self::setHasNoauthOnUrl();
    }

    public function setRequestOption($requestOption): void
    {
        $this->requestOption = $requestOption;
    }

    public function getRequestOption(): ?string
    {
        return $this->requestOption;
    }

    public function getTokenSessionName(): ?string
    {
        return $this->tokenSessionName;
    }

    public function setTokenSessionName($sessionName): void
    {
        $this->tokenSessionName = $sessionName;
    }

    public function setHasNoauthOnUrl(): void
    {
        $this->hasNoauth = true;
        if (!array_key_exists('NOAUTH', $this->requestUrl)) {
            $this->hasNoauth = false;
        }
    }

    public function isAuthorizedPage(): bool
    {
        $this->isAuthorized = false;
        if (($this->requestOption == 'dnd' || $this->requestOption == 'lge' || $this->requestOption == '') && !$this->hasNoauth) {
            if ($this->projectId == $this->getPidsArray()['DATADOWNLOADUSERS']) {
                $this->isAuthorized = true;
            }
        }
        return $this->isAuthorized;
    }

    public function getPidsArray(): array
    {
        if (empty($this->pidsArray)) {
            $hub_mapper = $this->module->getProjectSetting('hub-mapper', $this->projectId);
            if ($hub_mapper !== "") {
                $this->pidsArray = REDCapManagement::getPIDsArray($hub_mapper);
            }
        }
        return $this->pidsArray;
    }

    public function getSettingsData($projectId = null): array
    {
        if (empty($this->settings)) {
            if ($projectId == null) {
                $projectId = $this->pidsArray['SETTINGS'];
                if($projectId == null){
                    return [];
                }
            }
            $settings = \REDCap::getData($projectId, 'json-array', null)[0];

            if (!empty($settings)) {
                $settings = $this->module->escape($settings);
            } else {
                $settings = htmlspecialchars($settings, ENT_QUOTES);
            }

            #Escape name just in case they add quotes
            if (!empty($settings["hub_name"])) {
                $settings["hub_name"] = addslashes($settings["hub_name"]);
            }

            #Sanitize text title and descrition for pages
            $this->settings = ProjectData::sanitizeALLVariablesFromInstrument(
                $this->module,
                $projectId,
                [0 => "harmonist_text"],
                $settings
            );

            $this->hubName = $this->settings['hub_name'];
        }
        if ($this->isAuthorized) {
            $this->token = self::getTokenSession();
        }

        return $this->settings;
    }

    public function doesTokenExistInUrl(): bool {
        if(array_key_exists(self::SESSION_TOKEN_STRING, $this->requestUrl)){
            return true;
        }
        return false;
    }


    public function getTokenSession(): ?string
    {
        if (!self::retrieveSessionData()) {
            return null;
        }

        if (self::isSessionOut() || (isset($this->logOut) && $this->logOut)) {
            return null;
        }

        $token = self::getREDCapUserToken();
        $this->token = !is_null($token) ? $token : self::getToken();

        $this->logOut = false;
        return $this->token;
    }

    public function retrieveSessionData(): bool
    {
        #Retrieve session (with session_start) on other pages
        if (!self::doesTokenExistInUrl() && !array_key_exists(
                'request',
                $this->requestUrl
            ) && !empty($_SESSION[self::SESSION_TOKEN_STRING][$this->tokenSessionName]) && !array_key_exists(
                self::SESSION_OPTION_STRING,
                $this->requestUrl
            )) {
            #Login page
            return false;
        } else {
            if (empty($_SESSION[self::SESSION_TOKEN_STRING][$this->tokenSessionName]) || $this->isAuthorized) {
                session_start();
                return true;
            }
        }
        return false;
    }

    public function isSessionOut(): bool
    {
        if (array_key_exists(self::SESSION_OUT_STRING, $this->requestUrl)) {
            self::logOut();
            return true;
        }
        return false;
    }

    public function getTokenAndSessionFromSurveyReset(): ?string
    {
        // Must have a valid tokenSessionName or cookieKey() may throw
        if (!is_string($this->tokenSessionName) || trim($this->tokenSessionName) === '') {
            return null;
        }

        // Must have the session cookie to read anything
        if (!isset($_COOKIE[self::SESSION_COOKIE_STRING]) || $_COOKIE[self::SESSION_COOKIE_STRING] === '') {
            return null;
        }

        /** @var string $cookieRaw */
        $cookieRaw = $_COOKIE[self::SESSION_COOKIE_STRING];

        // Safety: ensure it's a reasonable string before decrypting
        if (!is_string($cookieRaw) || strlen($cookieRaw) > 8192) {
            return null;
        }

        $decrypted = $this->decryptCookieValue($cookieRaw);
        if ($decrypted === null) {
            return null;
        }

        // cookieKey may still throw if tokenSessionName has invalid chars/length
        try {
            $tokenSessionName = $this->cookieKey($this->tokenSessionName);
        } catch (\RuntimeException $e) {
            return null;
        }

        /** @var mixed $payload */
        $payload = json_decode($decrypted, true);
        if (!is_array($payload)) {
            return null;
        }

        /** @var mixed $tokenMap */
        $tokenMap = $payload[self::SESSION_TOKEN_STRING] ?? null;
        if (!is_array($tokenMap)) {
            return null;
        }

        /** @var mixed $token */
        $token = $tokenMap[$tokenSessionName] ?? null;
        if (!is_string($token) || !self::isTokenCorrect($token)) {
            return null;
        }

        // At this point $token is validated via isTokenCorrect() — safe to use
        /** @psalm-taint-escape cookie */
        $validatedToken = $token;

        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        $_SESSION[self::SESSION_TOKEN_STRING][$this->tokenSessionName] = $validatedToken;

        return $validatedToken;
    }

    public static function generateHexKey(): string
    {
        return bin2hex(random_bytes(32));
    }

    private function setSessionCookie(int $expiration, string $sessionData): void
    {
        $encrypted = $this->encryptCookieValue($sessionData);
        if ($encrypted === null) {
            return;
        }

        setcookie(self::SESSION_COOKIE_STRING, $encrypted, [
            'expires'  => $expiration,
            'path'     => '/',
            'secure'   => true,
            'httponly'  => true,
            'samesite' => 'None',
        ]);
    }

    /**
     * Cookie/JSON key safe identifier (no spaces/quotes/control chars).
     */
    private function cookieKey(string $key): string
    {
        $key = trim($key);

        // normalize disallowed characters to underscore
        $key = preg_replace('/[^a-zA-Z0-9_.:-]/', '_', $key);

        // enforce length (optional: hash instead)
        if (strlen($key) > 100) {
            $key = substr($key, 0, 60) . '_' . substr(hash('sha256', $key), 0, 12);
        }

        if ($key === '' || !preg_match('/\A[a-zA-Z0-9_.:-]+\z/', $key)) {
            throw new \RuntimeException('Invalid tokenSessionName: ' . $key . ', PID: ' . $this->projectId);
        }

        return $key;
    }

    /**
     * Derive a 32-byte encryption key from the project setting.
     */
    private function getCookieEncryptionKey(): string
    {
        $raw = $this->cookieKeyCrypt;
        if (!is_string($raw) || $raw === '') {
            throw new \RuntimeException('Cookie encryption key is not configured for PID: ' . $this->projectId);
        }

        return hash_hkdf('sha256', $raw, 32, 'harmonist_cookie_enc');
    }

    /**
     * Encrypt a string using AES-256-GCM.
     */
    private function encryptCookieValue(string $plaintext): ?string
    {
        $key = $this->getCookieEncryptionKey();
        $iv  = random_bytes(12);
        $tag = '';

        $ciphertext = openssl_encrypt($plaintext, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag, '', 16);
        if ($ciphertext === false) {
            return null;
        }

        return base64_encode($iv . $tag . $ciphertext);
    }

    /**
     * Decrypt a cookie value encrypted by encryptCookieValue().
     */
    private function decryptCookieValue(string $encoded): ?string
    {
        $key = $this->getCookieEncryptionKey();
        $raw = base64_decode($encoded, true);
        if ($raw === false || strlen($raw) < 28) {
            return null;
        }

        $iv         = substr($raw, 0, 12);
        $tag        = substr($raw, 12, 16);
        $ciphertext = substr($raw, 28);

        $plaintext = openssl_decrypt($ciphertext, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);
        if ($plaintext === false) {
            return null;
        }

        return $plaintext;
    }

    public function logOut(): void
    {
        unset($_SESSION[self::SESSION_TOKEN_STRING][$this->tokenSessionName]);
        unset($_SESSION[$this->tokenSessionName]);
        $this->token = null;
        $this->tokenSessionName = null;
        $this->logOut = true;
    }

    public function getREDCapUserToken(): ?string
    {
        $this->isAuthorized = self::isAuthorizedPage();
        #We check user first than token to ensure the hub refreshes to that user's account. Just in case someone tries to log in with someone else's token.
        if (
            ($this->isAuthorized && defined("USERID") && !empty(
                self::getTokenByUserId(
                    USERID
                )
                ))
            ||
            (!$this->isAuthorized && defined("USERID") && !array_key_exists(
                    self::SESSION_TOKEN_STRING,
                    $this->requestUrl
                ) && !array_key_exists(
                    'request',
                    $this->requestUrl
                ) && ((array_key_exists('option', $this->requestUrl) && $this->getRequestOption() === 'dnd')))
        ) {
            #If it's an Authorized page, user is logged in REDCap and user has a token
            #If it's a NOAUTH page, user is logged in REDCap and token is not in url and is Downloads page
            $_SESSION[self::SESSION_TOKEN_STRING] = [];
            $_SESSION[self::SESSION_TOKEN_STRING][$this->tokenSessionName] = self::getTokenByUserId(
                USERID
            );
            $token = $_SESSION[self::SESSION_TOKEN_STRING][$this->tokenSessionName];
            return $token;
        }
        return null;
    }

    public function setSessionDataFromToken($token): void
    {
        if (empty($token) || !is_string($token) || !self::isTokenCorrect($token)) {
            return;
        }

        // tokenSessionName must be valid before cookieKey() is called
        if (!is_string($this->tokenSessionName) || trim($this->tokenSessionName) === '') {
            return;
        }

        $tokenSessionName = $this->cookieKey($this->tokenSessionName);

        // Token is validated — mark as safe for Psalm taint analysis
        /** @psalm-taint-escape cookie */
        $safeToken = $token;

        // We set cookies as REDCap deletes Session data on redcap_survey_acknowledgement_page
        $sessionData = [
            self::SESSION_TOKEN_STRING => [
                $tokenSessionName => $safeToken
            ]
        ];

        $encoded = json_encode($sessionData);
        if ($encoded === false) {
            return;
        }

        $this->setSessionCookie(time() + 600, $encoded); // 10 minutes

        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        $this->token = $safeToken;
        $_SESSION[self::SESSION_TOKEN_STRING][$this->tokenSessionName] = $safeToken;
    }

    public function getTokenFromSurveyReset(): ?string{
        return $this->token;
    }

    public function getToken(): ?string
    {
        if (array_key_exists(
                self::SESSION_TOKEN_STRING,
                $this->requestUrl
            ) && !empty($this->requestUrl[self::SESSION_TOKEN_STRING])) {
            // Token is in the URL
            if (self::isTokenCorrect($this->requestUrl[self::SESSION_TOKEN_STRING])) {
                // Token is in url and is correct
                $token = $this->requestUrl[self::SESSION_TOKEN_STRING];
                $_SESSION[self::SESSION_TOKEN_STRING][$this->tokenSessionName] = $this->requestUrl[self::SESSION_TOKEN_STRING];
                return $token;
            } else {
                // Token is in url but is invalid — log out with expire message
                self::logOut();
                return null;
            }
        } else {
            if (!empty($_SESSION[self::SESSION_TOKEN_STRING][$this->tokenSessionName]) && self::isTokenCorrect(
                    $_SESSION[self::SESSION_TOKEN_STRING][$this->tokenSessionName]
                )) {
                // Token is in session and is correct
                $token = $_SESSION[self::SESSION_TOKEN_STRING][$this->tokenSessionName];
                return $token;
            }
        }
        return $this->getTokenAndSessionFromSurveyReset();
    }

    function getTokenByUserId($userid): ?string
    {
        $people = REDCap::getData(
            $this->pidsArray['PEOPLE'],
            'json-array',
            null,
            array('access_token'),
            null,
            null,
            false,
            false,
            false,
            "[redcap_name] = '" . $userid . "' AND [active_y] = '1'"
        )[0];
        if (!empty($people)) {
            return $people['access_token'];
        }
        return null;
    }

    public function isTokenCorrect($token)
    {
        $people = REDCap::getData(
            $this->pidsArray['PEOPLE'],
            'json-array',
            null,
            array('token_expiration_d'),
            null,
            null,
            false,
            false,
            false,
            "[access_token] = '" . $token . "' AND [active_y] = '1'"
        );
        if (!empty(arrayKeyExistsReturnValue($people,[0]))) {
            if (strtotime($people[0]['token_expiration_d']) > strtotime(date('Y-m-d'))) {
                return true;
            }
        }
        return false;
    }

    /**
     * Normalize a credentials directory path:
     * - preserves a leading "/" if the original was absolute
     * - guarantees exactly one trailing "/"
     */
    private function normalizeCredentialsPath(?string $path): string
    {
        // remove stray whitespace/newlines
        $path = trim((string) $path);
        if ($path === '') {
            return '';
        }

        // remember if it started with "/"
        // strip both ends
        // restore leading + add trailing
        $isAbsolute = ($path[0] === '/');
        $path = trim($path, '/');
        $path = ($isAbsolute ? '/' : '') . $path . '/';

        return $path;
    }

    public function getCredentialsServerVars($type): ?string
    {
        if (file_exists(
            $this->credentialsPath . $this->module->getProjectSetting(strtolower($type) . "-file", $this->getPidsArray()['PROJECTS'])

        )) {
            return $this->credentialsPath. $this->module->getProjectSetting(strtolower($type) . "-file", $this->getPidsArray()['PROJECTS']);
        }
        return null;
    }
}

?>
