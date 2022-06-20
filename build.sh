#!/bin/bash

SCRIPT=$(readlink -f $0)
SCRIPT_PATH=`dirname $SCRIPT`
BASE_PATH=`dirname $SCRIPT_PATH`

RETVAL=0
VERSION=0.4
SUBVERSION=2
IMAGE="bayrell/virtual_space"
TAG=`date '+%Y%m%d_%H%M%S'`

case "$1" in
	
	test)
		echo "Build $IMAGE:$VERSION.$SUBVERSION-$TAG"
		docker build ./ -t $IMAGE:$VERSION.$SUBVERSION-$TAG --file Dockerfile
		docker tag $IMAGE:$VERSION.$SUBVERSION-$TAG $IMAGE:$VERSION
		cd ..
	;;
	
	amd64)
		echo "Build $IMAGE:$VERSION.$SUBVERSION-amd64"
		docker build ./ -t $IMAGE:$VERSION.$SUBVERSION-amd64 \
			--file Dockerfile --build-arg ARCH=amd64
	;;
	
	arm64v8)
		echo "Build $IMAGE:$VERSION.$SUBVERSION-arm64v8"
		docker build ./ -t $IMAGE:$VERSION.$SUBVERSION-arm64v8 \
			--file Dockerfile --build-arg ARCH=arm64v8
	;;
	
	manifest)
		rm -rf ~/.docker/manifests/docker.io_virtual_space-*
		
		docker tag $IMAGE:$VERSION.$SUBVERSION-amd64 $IMAGE:$VERSION-amd64
		docker tag $IMAGE:$VERSION.$SUBVERSION-arm64v8 $IMAGE:$VERSION-arm64v8
		
		docker push $IMAGE:$VERSION.$SUBVERSION-amd64
		docker push $IMAGE:$VERSION.$SUBVERSION-arm64v8
		docker push $IMAGE:$VERSION-amd64
		docker push $IMAGE:$VERSION-arm64v8
		
		docker manifest create --amend $IMAGE:$VERSION.$SUBVERSION \
			$IMAGE:$VERSION.$SUBVERSION-amd64 \
			$IMAGE:$VERSION.$SUBVERSION-arm64v8
		docker manifest push --purge $IMAGE:$VERSION.$SUBVERSION
		
		docker manifest create --amend $IMAGE:$VERSION \
			$IMAGE:$VERSION-amd64 \
			$IMAGE:$VERSION-arm64v8
		docker manifest push --purge $IMAGE:$VERSION
	;;
	
	all)
		$0 amd64
		$0 arm64v8
		$0 manifest
	;;
	
	*)
		echo "Build $IMAGE:$VERSION.$SUBVERSION"
		echo "Usage: $0 {amd64|arm64v8|manifest|all|test}"
		RETVAL=1

esac

exit $RETVAL