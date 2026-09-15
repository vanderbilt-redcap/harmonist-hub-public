<div class="container">
    <div class="backTo">
        <a href="<?=$module->getUrl('index.php', true).'&option=dat'?>" class="text-decoration-none">< Back to Data</a>
    </div>
    <div class="optionSelect">
        <h3>Explore Data</h3>
        <p class="hub-title"></p>
    </div>
    <div>
        <div class="p-3">
            <iframe class="commentsform" id="explore-dab" src="" style="border: none; height: 860px; width: 100%;"></iframe>
        </div>
    </div>
</div>
<script>
    $(document).ready(function() {
        $('#explore-dab').attr('src', 'https://iedeaharmonist.app.vumc.org/dab/?tokendab=' + <?=json_encode(htmlentities($_REQUEST['tokendab'],ENT_QUOTES))?>);
    });
</script>
