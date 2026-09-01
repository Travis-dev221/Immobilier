<?php
$ok = file_put_contents(__DIR__.'/admin/panel.js', '// test') !== false;
echo $ok ? 'ECRITURE OK' : 'ERREUR: '.print_r(error_get_last(),true);
@unlink(__DIR__.'/test.php');
