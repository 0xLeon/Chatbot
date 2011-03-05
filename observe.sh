#!/bin/sh
code=2
while [ $code -eq "2" ]
do
php chatbot.php
code=$?
echo $code
done
