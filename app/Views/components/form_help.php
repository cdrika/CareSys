<?php
$keys=['email','password','password_confirm','username','purpose','name','region','regions','role','skill','skills','qualification','qualifications','work_type','type','state','decision','confirm','entries','workspace','visibility'];
$guide=[];foreach($keys as $key){[$text,$example]=\App\Libraries\FormCatalog::help('',$key,ucfirst(str_replace('_',' ',$key)));$guide[$key]=['text'=>$text,'example'=>$example];}
?>
<dialog id="field-help-dialog" class="help-dialog" aria-labelledby="field-help-title" aria-describedby="field-help-text"><div class="help-dialog-top"><span class="help-emblem" aria-hidden="true">?</span><button type="button" class="dialog-close" data-help-close aria-label="Close help">×</button></div><h2 id="field-help-title"></h2><p id="field-help-text"></p><div class="help-example"><h3>Example</h3><p id="field-help-example"></p></div><button type="button" data-help-close>Got it</button></dialog>
<script type="application/json" id="field-help-catalog"><?= json_encode($guide,JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT) ?></script>
