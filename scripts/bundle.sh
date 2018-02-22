rm -rf .tmp
mkdir .tmp && mkdir .tmp/satispay
cp -R code/* .tmp/satispay
cd .tmp && zip -r archive.zip satispay
cd ..
mv .tmp/archive.zip ../../.bundles/prestashop.zip
# rm -rf .tmp
