rm -rf tmp
mkdir -p tmp/satispay
cp -R controllers tmp/satispay
cp -R satispay-sdk tmp/satispay
cp -R translations tmp/satispay
cp -R views tmp/satispay
cp -R upgrade tmp/satispay
cp -R index.php tmp/satispay
cp -R logo.png tmp/satispay
cp -R satispay.php tmp/satispay
cp -R processpendingorderscommand.php tmp/satispay
cp -R ProcessPendingOrders.php tmp/satispay
cp -R config.xml tmp/satispay
cp -R config_it.xml tmp/satispay
cd tmp && find . -name ".DS_Store" -delete
zip -r satispay.zip . -x ".*" -x "__MACOSX"