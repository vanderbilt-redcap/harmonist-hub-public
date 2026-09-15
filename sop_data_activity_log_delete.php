<?php
namespace Vanderbilt\HarmonistHubPublicExternalModule;
require_once dirname(__FILE__) . "/classes/HubData.php";

if(ENVIRONMENT != "DEV") {
    //Do not require on localhost
    require_once ($module->getSecurityHandler()->getCredentialsServerVars("ENCRYPTION"));
    require_once ($module->getSecurityHandler()->getCredentialsServerVars("AWS"));
}

$deleteCode = $module->escape($_REQUEST['del']);
$file_name = $module->escape($_REQUEST['file_name']);
$current_user = $module->escape($_REQUEST['current_user']);
$deleteAwsUrl = preg_replace('/pid=(\d+)/', "pid=".$pidsArray['DATADOWNLOADUSERS'],$module->getUrl('hub/aws/AWS_deleteFile.php'))."&codeData=".$deleteCode;
?>

<div class="container my-5" style="min-height: 900px;">
    <form class="row g-3" action="<?=$deleteAwsUrl?>" method="post" id="deleteAwsData">
        <div>
            <h5 class="modal-title">Delete Data Upload</h5>
        </div>
        <div class="mt-3">
            <span>Provide a reason for deleting <strong><?=$file_name?></strong> data upload:</span>
            <br><br>
            <textarea name="deletion_rs" id="deletion_rs" class="form-control" rows="4"></textarea>
            <div id="dataUploadError" class="text-danger mt-2"></div>
            <button type="submit" form="deleteAwsData" class="btn btn-danger mt-3" id="btnModalRescheduleForm">Delete</button>
        </div>
    </form>
    <div class="py-5"></div>
    <?php include('hub_footer.php'); ?>
    <br>
</div>
</body>
</html>
