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

# Get root pass (to delete the database and the user)
mysql_root_pass=""
status_code=1

while [ $status_code -ne 0 ]; do
  read -rp "MySQL root password: " -s mysql_root_pass
  echo
  echo "SHOW DATABASES" | mysql -u root --password="${mysql_root_pass}" &>/dev/null
  status_code=$?
done

php_config="${www}/include/config.php"
if [ -f "${www}/include/config_local.php" ]; then
  php_config="${www}/include/config_local.php"
fi

mysql_db_user=$(sed -n "s/^.*db_user = '\(.*\)'.*$/\1/p" "${php_config}")

if [ "${mysql_db_user}" = "" ]; then
  echo "Can't find the MySQL db user. Please ensure your ${php_config} is well structured or report an issue"
  exit
fi

mysql_db_name=$(sed -n "s/^.*db_name = '\(.*\)'.*$/\1/p" "${php_config}")

if [ "${mysql_db_name}" = "" ]; then
  echo "Can't find the MySQL db name. Please ensure your ${php_config} is well structured or report an issue"
  exit
fi

echo -e "\033[1mAre you sure to completely delete OpenVPN configurations, the web application (with the MySQL user/database) and the iptables rules? (yes/*)\033[0m"
read -r agree

if [ "${agree}" != "yes" ]; then
  exit
fi

# MySQL delete
echo "DROP USER ${mysql_db_user}@localhost" | mysql -u root --password="${mysql_root_pass}"
echo "DROP DATABASE \`${mysql_db_name}\`" | mysql -u root --password="${mysql_root_pass}"

# Files delete (openvpn configs/keys + web application)
rm -r /etc/openvpn/easy-rsa/
rm -r /etc/openvpn/{ccd,scripts,server.conf,ca.crt,ta.key,server.crt,server.key,dh*.pem}
rm -r "${www}"

# Remove rooting rules
echo 0 >"/proc/sys/net/ipv4/ip_forward"
sed -i '/net.ipv4.ip_forward = 1/d' '/etc/sysctl.conf'

# Get primary NIC device name
primary_nic=$(ip r | grep '^default' | grep -Po '(?<=(dev )).*(?= proto)')

# Iptables rules
iptables -D FORWARD -i tun+ -j ACCEPT
iptables -D FORWARD -o tun+ -j ACCEPT
iptables -D OUTPUT -o tun+ -j ACCEPT

iptables -D FORWARD -i tun+ -o "${primary_nic}" -j ACCEPT
iptables -t nat -D POSTROUTING -o "${primary_nic}" -j MASQUERADE

echo "The application has been completely removed!"
