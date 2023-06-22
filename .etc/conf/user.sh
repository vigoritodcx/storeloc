LUID=1000
LGID=1000
groupadd -g $LGID samegroup
useradd -g $LGID -u $LUID -s /bin/bash sameuser -m
usermod -a -G sudo sameuser
