
if [ -z $1 ]
then
	PROGRESS_FILE=$1
fi

if [ -z $PROGRESS_FILE ]
then
	exit 1
	fi

echo 0 > $PROGRESS_FILE
echo "***********************************"
echo "*   Lauch install of dependency   *"
echo "***********************************"

date

echo 5 > $PROGRESS_FILE
apt-get clean
echo 30 > $PROGRESS_FILE
apt-get update
echo 50 > $PROGRESS_FILE

echo "***********************************"
echo "*  Install modules using apt-get  *"
echo "***********************************"
apt-get install -y python3 python3-requests
echo 60 > $PROGRESS_FILE
apt-get install -y python3 python3-websocket
echo 70 > $PROGRESS_FILE
apt-get install -y python3 python3-msgpack
echo 90 > $PROGRESS_FILE

echo "***********************************"
echo "*       Install ended             *"
echo "***********************************"
echo 100 > $PROGRESS_FILE
date

# rm $PROGRESS_FILE
exit 0
