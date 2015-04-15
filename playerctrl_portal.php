<script type="text/javascript" language="javascript">
</script>
<?php
require_once 'commonfunc.php';

$playerkind = getcurrentplayer();

if( strcmp('foobar', $playerkind) === 0 ) {
    include('foobarctl.php');
}else {
    include('mpcctrl.php');
}

?>
