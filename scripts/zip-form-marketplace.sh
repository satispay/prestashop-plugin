rm -rf tmp
mkdir -p tmp/satispay
cp -R controllers tmp/satispay
cp -R satispay-sdk tmp/satispay
cp -R translations tmp/satispay
cp -R views tmp/satispay
cp -R index.php tmp/satispay
cp -R logo.png tmp/satispay
cp -R satispay.php tmp/satispay
(cd tmp && zip -r archive.zip satispay)
