#!/bin/bash

# Logando em PRD
server=192.168.10.1
ssh -tt -oStrictHostKeyChecking=no -i devops/privatekey.pem ec2-user@$server "cd /webserver/sites/app/;sudo git pull"