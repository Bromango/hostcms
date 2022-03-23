@ECHO OFF
setlocal DISABLEDELAYEDEXPANSION
SET BIN_TARGET=%~dp0/../gam6itko/ozon-seller/bin/is_realized.php
php "%BIN_TARGET%" %*
