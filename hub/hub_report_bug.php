<div class="container">
    <h3>Issue Report Survey</h3>
    <p class="hub-title"></p>
</div>

<?php
if (array_key_exists('message', $_REQUEST) && ($_REQUEST['message'] == 'R')) {
    ?>
    <div class="alert alert-success col-12 fade show border-success d-none" id="succMsgContainer">
        Bug successfully reported..
    </div>
    <?php
}
?>

<div>
    <div class="accordion-collapse collapse show" aria-expanded="true">
        <div class="accordion-body">
            <iframe class="commentsform border-0 w-100" id="redcap-frame" message="R" src="https://redcap.vumc.org/surveys/?s=3RKCREXK7R" style="height: 860px;"></iframe>
        </div>
    </div>
</div>