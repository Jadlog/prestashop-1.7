#!/usr/bin/env bash
rm package/jadlog-prestashop.zip
cd src
zip -r ../package/jadlog-prestashop.zip jadlog/*
cd ..
ls -lah package/jadlog-prestashop.zip
unzip -l package/jadlog-prestashop.zip

