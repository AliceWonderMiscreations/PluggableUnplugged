#!/bin/bash
find . -type f -print |grep "~$" |while read file; do
  rm -f ${file}
done

TMP=`mktemp -d /tmp/plugin.XXXXXXXX`
mkdir -p ${TMP}/awm-pluggable-unplugged/lib

cp TGMPA-GPLv2-LICENSE.md ${TMP}/LICENSE.txt

cd lib
for code in AdminMenu.php Groovytar.php InvalidArgumentException.php UnpluggedStatic.php WordPressGroovytar.php; do
  cat ${code} \
  |sed -e s?"https://opensource.org/licenses/MIT MIT"?"https://opensource.org/licenses/GPL-2.0 GPL-2.0"? \
  > ${TMP}/awm-pluggable-unplugged/lib/${code}
done
cd ..
cat pluggable-unplugged.php \
  |sed -e s?"https://opensource.org/licenses/MIT MIT"?"https://opensource.org/licenses/GPL-2.0 GPL-2.0"? \
  > ${TMP}/awm-pluggable-unplugged/pluggable-unplugged.php

cp readme.txt ${TMP}/awm-pluggable-unplugged/

pushd ${TMP}

zip -r awm-pluggable-unplugged.zip awm-pluggable-unplugged

popd

cp ${TMP}/awm-pluggable-unplugged.zip .
