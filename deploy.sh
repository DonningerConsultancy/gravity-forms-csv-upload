#!/bin/bash
rsync -azPv --exclude '.git' --exclude '.gitignore' --exclude 'deploy.sh' --delete * trimbos@10.108.30.20:/home/trimbos/drugsincidenten.nl/wp-content/plugins/gravity-forms-csv-upload/

