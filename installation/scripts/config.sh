#!/bin/bash

# MySQL credentials
HOST='localhost'
PORT='3306'
USER='openvpn-admin'
PASS='openvpn-admin'
DB='openvpn-admin'

if [ -f "/etc/openvpn/scripts/config_local.sh" ]
then
  . /etc/openvpn/scripts/config_local.sh
fi
