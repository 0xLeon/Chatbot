#!/bin/sh
# Starts the bot and restarts it, when it returns status "2"
#
# @author 	Tim Düsterhus
# @copyright 	2010 -2011 Tim Düsterhus

code=2
while [ $code -eq "2" ]
do
php chatbot.php
code=$?	
done