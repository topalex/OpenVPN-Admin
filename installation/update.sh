#!/bin/bash

print_help() {
  echo -e "${0} base_dir"
  echo -e "\tbase_dir: The place where the web application is in"
}

# Ensure to be root
if [ "${EUID}" -ne 0 ]; then
  echo "Please run as root"
  exit
fi

# Ensure there are enough arguments
if [ "${#}" -ne 1 ]; then
  print_help
  exit
fi

www=${1%/}

if [ ! -d "${www}" ]; then
  echo -e "directory ${www} does not exist"
  exit
fi

base_path=$(dirname "$(readlink -f "${0}")")

# shellcheck disable=SC2012
user=$(ls -l "${www}/include/config.php" | awk '{ print $3 }')
# shellcheck disable=SC2012
group=$(ls -l "${www}/include/config.php" | awk '{ print $4 }')

if [ ! -f "${www}/include/config_local.php" ]; then
  cp -p "${www}/include/config.php" "${www}/include/config_local.php"
fi

rm -rf "${www:?}/"{index.php,grids.php,bower.json,.bowerrc,js,css,vendor}
rm -rf "${www:?}/public/"{index.php,grids.php,js,css,vendor}
rm -rf "${www:?}/include/"{config.php,template,html,connect.php,functions.php,grids.php,management.php}

cp -r "${base_path}/../"{bower.json,.bowerrc} "${www}"
cp -r "${base_path}/../public/"{index.php,grids.php,js,css} "${www}/public"
cp -r "${base_path}/../include/"{template,config.php,connect.php,functions.php,management.php} "${www}/include"

cd "${www}" || exit

bower --allow-root install

cd "${base_path}" || exit

chown -R "${user}:${group}" "${www}"

if [ ! -f "/etc/openvpn/scripts/config_local.sh" ]; then
  cp -p "/etc/openvpn/scripts/config.sh" "/etc/openvpn/scripts/config_local.sh"
fi

rm -f "/etc/openvpn/scripts/"{config.sh,connect.sh,disconnect.sh,login.sh,functions.sh}
cp "${base_path}/scripts/"{config.sh,connect.sh,disconnect.sh,login.sh,functions.sh} "/etc/openvpn/scripts"
chmod +x "/etc/openvpn/scripts/"{connect.sh,disconnect.sh,login.sh}

echo "Processing database migration..."

cp "${base_path}/migration.php" "${www}/migration.php"
php "${www}/migration.php"
rm -f "${www}/migration.php"

echo "Database migrations done."

echo "OpenVPN-admin upgraded."
