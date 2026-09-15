<script src="https://cdnjs.cloudflare.com/ajax/libs/iframe-resizer/3.6.2/iframeResizer.min.js" integrity="sha256-aYf0FZGWqOuKNPJ4HkmnMZeODgj3DVslnYf+8dCN9/k=" crossorigin="anonymous"></script>
<script type="text/javascript" src="<?=$module->getUrl('js/jquery-3.7.1.min.js')?>"></script>
<script type="text/javascript" src="<?=$module->getUrl('js/jquery-ui.min.js')?>"></script>
<script type="text/javascript" src="<?=$module->getUrl('bootstrap-5.3.8/js/bootstrap.bundle.js')?>"></script>

<script type="text/javascript" src="<?=$module->getUrl('js/sortable.min.js')?>"></script>

<script type="text/javascript" src="<?=$module->getUrl('js/jquery.dataTables.min.js')?>"></script>
<script type="text/javascript" src="<?=$module->getUrl('js/dataTables.select.js')?>"></script>
<script type="text/javascript" src="<?=$module->getUrl('js/dataTables.buttons.min.js')?>"></script>
<script type="text/javascript" src="<?=$module->getUrl('js/buttons.flash.min.js')?>"></script>
<script type="text/javascript" src="<?=$module->getUrl('js/buttons.html5.min.js')?>"></script>
<script type="text/javascript" src="<?=$module->getUrl('js/buttons.print.min.js')?>"></script>
<script type="text/javascript" src="<?=$module->getUrl('js/jszip.min.js')?>"></script>
<script type="text/javascript" src="<?=$module->getUrl('js/pdfmake.min.js')?>"></script>
<script type="text/javascript" src="<?=$module->getUrl('js/vfs_fonts.js')?>"></script>
<script type='text/javascript' src="<?=APP_PATH_WEBROOT_ALL .'Resources/webpack/css/tinymce/tinymce.min.js'?>"> </script>
<script type="text/javascript" src="<?=$module->getUrl('js/Chart.min.js')?>"></script>
<script type="text/javascript" src="<?=$module->getUrl('js/chartjs-plugin-labels.js')?>"></script>
<script type='text/javascript' href='<?=$module->getUrl('manifest.json')?>'></script>
<script type="text/javascript" src="<?=$module->getUrl('js/dataTables.responsive.min.js')?>"></script>

<script type="text/javascript" src="<?=$module->getUrl('js/functions.js')?>"></script>

<link type='text/css' href='<?=$module->getUrl('css/sortable-theme-bootstrap.css')?>' rel='stylesheet' media='screen' />
<link type='text/css' href='<?=$module->getUrl('css/custom.min.css')?>' rel='stylesheet' media='screen' />
<link type='text/css' href='<?=$module->getUrl('css/jquery.dataTables.min.css')?>' rel='stylesheet' media='screen' />
<link type='text/css' href='<?=$module->getUrl('css/style.css')?>' rel='stylesheet' media='screen' />
<link type='text/css' href='<?=$module->getUrl('css/tabs-steps-menu.css')?>' rel='stylesheet' media='screen' />
<link type='text/css' href='<?=$module->getUrl('css/jquery-ui.min.css')?>' rel='stylesheet' media='screen' />
<link type='text/css' href='<?=$module->getUrl('js/fonts-awesome/css/font-awesome.min.css')?>' rel='stylesheet' media='screen' />
<link rel="stylesheet" href='<?=$module->getUrl('css/responsive.jqueryui.min.css')?>'/>
<link rel="stylesheet" href='<?=$module->getUrl('css/responsive.dataTables.min.css')?>'/>

<script language="JavaScript">
    document.addEventListener('DOMContentLoaded', function () {
        // Enable Bootstrap 5 tooltips globally
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    });
</script>
