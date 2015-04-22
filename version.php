<?php

// We only can count up. The 4. digit is only for the internal patchlevel to trigger DB upgrades
// between betas, final and RCs. This is _not_ the public version number. Reset minor/patchlevel
// when updating major/minor version number.
$OC_Version=array(8, 0, 3, 2);

// The human readable string
$OC_VersionString='8.0.3 RC3';

// The ownCloud channel
$OC_Channel='git';

// The build number
$OC_Build='';

