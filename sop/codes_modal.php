<?php namespace Vanderbilt\HarmonistHubPublicExternalModule;
$modalId = $codeformat['modal_id']
        ?? 'codesModal_' . preg_replace('/[^A-Za-z0-9_-]/', '_', $codeformat['code_file']);
$modalIdEsc = htmlspecialchars($modalId, ENT_QUOTES);
?>

<!-- Modal -->
<div class="modal fade" id="<?= $modalIdEsc ?>" tabindex="-1" aria-labelledby="<?= $modalIdEsc ?>" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="<?= $modalIdEsc ?>">Codes</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <table class="table sortable-theme-bootstrap sop_modal_table" data-sortable>
                        <?PHP
                        $csv = parseCSVtoArray($module, $codeformat['code_file']);
                        if(empty($csv)){
                            ?><div style="text-align: center; color:red;">No Codes found for file: <?= htmlspecialchars($codeformat['code_file'], ENT_QUOTES) ?></div><?PHP
                        }
                        foreach ($csv as $header => $content){
                            if($header == 0){
                                ?><tr class="sop_modal_header"><?PHP
                            }else{
                                ?><tr><?PHP
                            }
                            foreach ($content as $col => $value) {
                                $value = mb_convert_encoding($value, 'UTF-8');
                                if($header == 0){
                                    ?><td class="code_modal_td"><?= htmlspecialchars($col, ENT_QUOTES) ?></td><?PHP
                                }else{
                                    ?><td class="code_modal_td"><?= htmlspecialchars($value, ENT_QUOTES) ?></td><?PHP
                                }
                            }
                            ?></tr><?PHP
                        }
                        ?>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">CLOSE</button>
            </div>
        </div>
    </div>
</div>