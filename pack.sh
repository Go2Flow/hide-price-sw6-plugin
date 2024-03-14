#!/bin/sh
GREEN='\033[0;32m'
RED='\033[0;31m'
NC='\033[0m' # No Color
if [ -z "$1" ]
  then
    echo "${RED}No arguments supplied. Please name the Version${NC}"
    exit 1
fi
echo "${GREEN}-----start packing-----${NC}"
echo
cp -rp Go2FlowInviteCustomer Archive/tmp/Go2FlowInviteCustomer/
cd Archive/tmp/
echo
zip -r Go2FlowInviteCustomer-$1.zip . -x '**/.*' -x '**/__MACOSX'
mv Go2FlowInviteCustomer-$1.zip ../
rm -rf ./*
cd ../../
echo
echo "${GREEN}-----done!-----${NC}"
