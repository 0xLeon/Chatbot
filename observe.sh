#!/bin/sh
# Starts the bot and restarts it, when it returns status "2"
#
# @author 	Tim D�sterhus
# @copyright 	2010 -2011 Tim D�sterhus

code=2
while [ $code -eq "2" ]
do
php chatbot.php
code=$?	
done