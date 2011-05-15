# Builds LimelightBundle dependencies
#!/bin/sh

DIR=`php -r "echo dirname(dirname(realpath('$0')));"`
BIN="$DIR/bin"
VENDOR="$DIR/vendor"

# Run the symfony build script
if [ "$1" = "--reinstall" -o "$2" = "--reinstall" ]; then
    $DIR/bin/vendors.sh --reinstall
else
    $DIR/bin/vendors.sh
fi

# Add the FOS/UserBundle submodule
mkdir -p "$VENDOR/bundles/FOS"
git submodule add git://github.com/FriendsOfSymfony/UserBundle.git vendor/bundles/FOS/UserBundle