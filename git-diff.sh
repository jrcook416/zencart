#!/bin/bash
git diff --name-only > dotWebsiteDiffs.txt 12c7f2a70cef8134f726fe53c8f50a25f2521657..34fa54d5782d139352b0d1ab020c2183861cf52f -- . ':!*.gif' ':!*.jpg' ':*.jpeg'
