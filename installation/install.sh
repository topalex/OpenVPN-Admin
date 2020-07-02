#!/bin/bash

print_help() {
  echo -e "${0} base_dir user group"
  echo -e "\tbase_dir: The place where the web application will be put in"
  echo -e "\tuser:     User of the web application"
  echo -e "\tgroup:    Group of the web application"
}

# Ensure to be root
if [ "${EUID}" -ne 0 ]; then
  echo "Please run as root"
  exit
fi

# Ensure there are enough arguments
if [ "${#}" -ne 3 ]; then
  print_help
  exit
fi

# Ensure there are the prerequisites
for i in openvpn mysql php bower node unzip wget sed; do
  if ! command -v $i >/dev/null; then
    echo "Miss ${i}"
    exit
  fi
done

www=${1%/}
user=$2
group=$3

# Check the validity of the arguments

if [ ! -d "${www}" ]; then
  echo -e "directory ${www} does not exist"
  exit
fi

if ! grep -q "${user}" "/etc/passwd"; then
  echo -e "user ${user} does not exist"
  exit
fi

if ! grep -q "${group}" "/etc/group"; then
  echo -e "group ${group} does not exist"
  exit
fi

base_path=$(dirname "$(readlink -f "${0}")")

printf "\n################## Server information ##################\n"

read -rp "Server Hostname/IP: " ip_server

read -rp "OpenVPN protocol (tcp or udp) [tcp]: " openvpn_proto

if [[ -z $openvpn_proto ]]; then
  openvpn_proto="tcp"
fi

read -rp "Port [443]: " server_port

if [[ -z $server_port ]]; then
  server_port="443"
fi

# Get root pass (to create the database and the user)
mysql_root_pass=""
status_code=1

while [ $status_code -ne 0 ]; do
  read -rp "MySQL root password: " -s mysql_root_pass
  echo
  echo "SHOW DATABASES" | mysql -u root --password="${mysql_root_pass}" &>/dev/null
  status_code=$?
done

read -rp "MySQL database name for OpenVPN-Admin (will be created) [openvpn-admin]: " mysql_db_name

if [[ -z $mysql_db_name ]]; then
  mysql_db_name="openvpn-admin"
fi

sql_result=$(echo "SHOW DATABASES" | mysql -u root --password="${mysql_root_pass}" | grep -e "^${mysql_db_name}$")
# Check if the database doesn't already exist
if [ "${sql_result}" != "" ]; then
  echo "MySQL database ${mysql_db_name} already exists."
  exit
fi

# Check if the user doesn't already exist
read -rp "MySQL user name for OpenVPN-Admin (will be created) [openvpn-admin]: " mysql_db_user

if [[ -z $mysql_db_user ]]; then
  mysql_db_user="openvpn-admin"
fi

if echo "SHOW GRANTS FOR ${mysql_db_user}@localhost" | mysql -u root --password="${mysql_root_pass}" &>/dev/null; then
  echo "MySQL user ${mysql_db_user} already exists."
  exit
fi

read -rp "MySQL user password for OpenVPN-Admin: " -s mysql_db_pass
echo

# TODO MySQL port & host ?

printf "\n################## Certificates informations ##################\n"

read -rp "Key size (1024, 2048 or 4096) [2048]: " key_size

read -rp "Root certificate expiration (in days) [3650]: " ca_expire

read -rp "Certificate expiration (in days) [3650]: " cert_expire

read -rp "Country Name (2 letter code) [US]: " cert_country

read -rp "State or Province Name (full name) [California]: " cert_province

read -rp "Locality Name (eg, city) [San Francisco]: " cert_city

read -rp "Organization Name (eg, company) [Copyleft Certificate Co]: " cert_org

read -rp "Organizational Unit Name (eg, section) [My Organizational Unit]: " cert_ou

read -rp "Email Address [me@example.net]: " cert_email

read -rp "Common Name (eg, your name or your server's hostname) [ChangeMe]: " key_cn

printf "\n################## Creating the certificates ##################\n"

# Get the rsa keys
wget "https://github.com/OpenVPN/easy-rsa/releases/download/v3.0.6/EasyRSA-unix-v3.0.6.tgz"
tar -xaf "EasyRSA-unix-v3.0.6.tgz"
mv "EasyRSA-v3.0.6" /etc/openvpn/easy-rsa
rm "EasyRSA-unix-v3.0.6.tgz"

cd /etc/openvpn/easy-rsa || exit

if [[ -n $key_size ]]; then
  export EASYRSA_KEY_SIZE=$key_size
fi
if [[ -n $ca_expire ]]; then
  export EASYRSA_CA_EXPIRE=$ca_expire
fi
if [[ -n $cert_expire ]]; then
  export EASYRSA_CERT_EXPIRE=$cert_expire
fi
if [[ -n $cert_country ]]; then
  export EASYRSA_REQ_COUNTRY=$cert_country
fi
if [[ -n $cert_province ]]; then
  export EASYRSA_REQ_PROVINCE=$cert_province
fi
if [[ -n $cert_city ]]; then
  export EASYRSA_REQ_CITY=$cert_city
fi
if [[ -n $cert_org ]]; then
  export EASYRSA_REQ_ORG=$cert_org
fi
if [[ -n $cert_ou ]]; then
  export EASYRSA_REQ_OU=$cert_ou
fi
if [[ -n $cert_email ]]; then
  export EASYRSA_REQ_EMAIL=$cert_email
fi
if [[ -n $key_cn ]]; then
  export EASYRSA_REQ_CN=$key_cn
fi

# Init PKI dirs and build CA certs
./easyrsa init-pki
./easyrsa build-ca nopass
# Generate Diffie-Hellman parameters
./easyrsa gen-dh
# Generate server keypair
./easyrsa build-server-full server nopass

# Generate shared-secret for TLS Authentication
openvpn --genkey --secret pki/ta.key

printf "\n################## Setup OpenVPN ##################\n"

cd "${base_path}" || exit

# Copy certificates and the server configuration in the openvpn directory
cp /etc/openvpn/easy-rsa/pki/{ca.crt,ta.key,issued/server.crt,private/server.key,dh.pem} "/etc/openvpn/"
cp "${base_path}/server.conf" "/etc/openvpn/"
mkdir "/etc/openvpn/ccd"
sed -i "s/port 443/port ${server_port}/" "/etc/openvpn/server.conf"

if [ $openvpn_proto = "udp" ]; then
  sed -i "s/proto tcp/proto ${openvpn_proto}/" "/etc/openvpn/server.conf"
fi

nobody_group=$(id -ng nobody)
sed -i "s/group nogroup/group ${nobody_group}/" "/etc/openvpn/server.conf"

printf "\n################## Setup firewall ##################\n"

# Make ip forwarding and make it persistent
echo 1 >"/proc/sys/net/ipv4/ip_forward"
echo "net.ipv4.ip_forward = 1" >>"/etc/sysctl.conf"

# Get primary NIC device name
primary_nic=$(ip r | grep '^default' | grep -Po '(?<=(dev )).*(?= proto)')

# Iptables rules
iptables -I FORWARD -i tun+ -j ACCEPT
iptables -I FORWARD -o tun+ -j ACCEPT
iptables -I OUTPUT -o tun+ -j ACCEPT

iptables -A FORWARD -i tun+ -o "${primary_nic}" -j ACCEPT
iptables -t nat -A POSTROUTING -o "${primary_nic}" -j MASQUERADE

printf "\n################## Setup MySQL database ##################\n"

echo "CREATE DATABASE \`${mysql_db_name}\`" | mysql -u root --password="${mysql_root_pass}"
echo "CREATE USER ${mysql_db_user}@localhost IDENTIFIED BY '${mysql_db_pass}'" | mysql -u root --password="${mysql_root_pass}"
echo "GRANT ALL PRIVILEGES ON \`${mysql_db_name}\`.*  TO ${mysql_db_user}@localhost" | mysql -u root --password="${mysql_root_pass}"
echo "FLUSH PRIVILEGES" | mysql -u root --password="${mysql_root_pass}"

printf "\n################## Setup web application ##################\n"

# Copy bash scripts (which will insert row in MySQL)
cp -r "${base_path}/scripts" "/etc/openvpn/"
chmod +x "/etc/openvpn/scripts/"{connect.sh,disconnect.sh,login.sh}

# Configure MySQL in openvpn scripts
{
  echo '#!/bin/bash'
  echo
  echo "USER='${mysql_db_user}'"
  echo "PASS='${mysql_db_pass}'"
  echo "DB='${mysql_db_name}'"
} >>"/etc/openvpn/scripts/config_local.sh"

cp -r "${base_path}/"{sql,client-conf} "${www}"
cp -r "${base_path}/../"{public,bower.json,.bowerrc,include} "${www}"

# Configure MySQL in config.php variables
{
  echo "<?php"
  echo
  echo "\$db_user = '${mysql_db_user}';"
  echo "\$db_pass = '${mysql_db_pass}';"
  echo "\$db_name = '${mysql_db_name}';"
} >>"${www}/include/config_local.php"

# Replace in the client configurations with the ip of the server and openvpn protocol
for file in "${www}"/client-conf/**/client.ovpn; do
  sed -i "s/remote xxx\.xxx\.xxx\.xxx 443/remote ${ip_server} ${server_port}/" "${file}"
  {
    echo "<ca>"
    cat "/etc/openvpn/ca.crt"
    echo "</ca>"
    echo "<tls-auth>"
    cat "/etc/openvpn/ta.key"
    echo "</tls-auth>"
  } >>"${file}"

  if [ $openvpn_proto = "udp" ]; then
    sed -i "s/proto tcp-client/proto udp/" "${file}"
  fi
done

cd "${www}" || exit

# Install third parties
bower --allow-root install

cd "${base_path}" || exit

chown -R "${user}:${group}" "${www}"

printf "\033[1m\n#################################### Finish ####################################\n"

echo -e "# Congratulations, you have successfully setup OpenVPN-Admin! #\r"
echo -e "Please, finish the installation by configuring your web server (Apache, NGinx...)"
echo -e "and install the web application by visiting http://your-installation/index.php?installation\r"
echo -e "Then, you will be able to run OpenVPN with systemctl start openvpn@server\r"
echo "Please, report any issues here https://github.com/Chocobozzz/OpenVPN-Admin"
printf "\n################################################################################ \033[0m\n"
