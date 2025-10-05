#!/bin/bash

SVN_URL="https://plugins.svn.wordpress.org/trust-badges"
SVN_DIR="svn"
RELEASE_DIR="../release/trust-badges"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
NC='\033[0m' # No Color

echo -e "${BLUE}🚀 WordPress.org SVN Deployment Script${NC}"
echo -e "${CYAN}Plugin: trust-badges${NC}"
echo -e "${CYAN}SVN URL: $SVN_URL${NC}"
echo ""

# Ask for credentials
echo -e "${YELLOW}👤 Enter your WordPress.org username:${NC}"
read -r SVN_USERNAME

echo -e "${YELLOW}🔐 Enter your WordPress.org password:${NC}"
read -rs SVN_PASSWORD
echo ""

if [ -z "$SVN_USERNAME" ] || [ -z "$SVN_PASSWORD" ]; then
    echo -e "${RED}❌ Username and password are required.${NC}"
    exit 1
fi

# Check if svn folder exists
if [ ! -d "$SVN_DIR" ]; then
    echo -e "${BLUE}📥 SVN folder not found. Checking out from $SVN_URL...${NC}"
    svn checkout "$SVN_URL" "$SVN_DIR" --username "$SVN_USERNAME"
    if [ $? -ne 0 ]; then
        echo -e "${RED}❌ Failed to checkout SVN repository.${NC}"
        exit 1
    fi
    echo -e "${GREEN}✅ SVN repository checked out successfully!${NC}"
else
    echo -e "${BLUE}📥 SVN folder exists. Updating...${NC}"
    cd "$SVN_DIR"
    svn update
    if [ $? -ne 0 ]; then
        echo -e "${RED}❌ Failed to update SVN repository.${NC}"
        exit 1
    fi
    echo -e "${GREEN}✅ SVN repository updated successfully!${NC}"
    cd ..
fi

cd "$SVN_DIR"

# Check if release directory exists
if [ ! -d "$RELEASE_DIR" ]; then
    echo -e "${RED}❌ Release directory not found at $RELEASE_DIR${NC}"
    echo -e "${YELLOW}💡 Please run 'yarn build' first to create the release directory${NC}"
    exit 1
fi

echo -e "${BLUE}🤔 What type of change do you want to push?${NC}"
echo -e "${CYAN}  1) assets - Update readme.txt and .wordpress-org files${NC}"
echo -e "${CYAN}  2) tag - Create a new version tag${NC}"
read -r CHANGE_TYPE

if [ "$CHANGE_TYPE" = "assets" ] || [ "$CHANGE_TYPE" = "1" ]; then
    echo -e "${BLUE}📋 Pushing assets (readme.txt and .wordpress-org) to SVN...${NC}"
    cp ../readme.txt trunk/readme.txt
    if [ -d "../.wordpress-org" ]; then
        cp -r ../.wordpress-org/* assets/
        echo -e "${GREEN}✅ Copied .wordpress-org files to assets/${NC}"
    fi
    svn add --force assets/* trunk/readme.txt
    svn commit -m "Update assets and readme.txt" --username "$SVN_USERNAME" --password "$SVN_PASSWORD"
    if [ $? -eq 0 ]; then
        echo -e "${GREEN}✅ Assets updated successfully!${NC}"
    else
        echo -e "${RED}❌ Failed to commit assets.${NC}"
        exit 1
    fi
    exit 0
fi

if [ "$CHANGE_TYPE" = "tag" ] || [ "$CHANGE_TYPE" = "2" ]; then
    echo -e "${YELLOW}📝 Enter the new version number (e.g., 1.2.3):${NC}"
    read -r VERSION
    TAG_DIR="tags/$VERSION"
    
    echo -e "${BLUE}🏷️  Creating version tag $VERSION...${NC}"
    
    # Remove old tag if exists
    if [ -d "$TAG_DIR" ]; then
        echo -e "${YELLOW}⚠️  Removing existing tag $VERSION...${NC}"
        svn rm "$TAG_DIR" --force
    fi
    mkdir -p "$TAG_DIR"
    svn add "$TAG_DIR"

    # List of files/folders to copy
    FILES=(.wordpress-org assets includes vendor composer.json readme.txt trust-badges.php)
    echo -e "${BLUE}📋 Copying files to trunk and tag...${NC}"
    for ITEM in "${FILES[@]}"; do
        if [ -e "$RELEASE_DIR/$ITEM" ]; then
            cp -r "$RELEASE_DIR/$ITEM" trunk/
            cp -r "$RELEASE_DIR/$ITEM" "$TAG_DIR/"
            echo -e "${GREEN}✅ Copied $ITEM${NC}"
        else
            echo -e "${YELLOW}⚠️  $ITEM not found in release directory${NC}"
        fi
    done

    svn add --force trunk/* "$TAG_DIR"/*
    svn commit -m "Release version $VERSION" --username "$SVN_USERNAME" --password "$SVN_PASSWORD"
    if [ $? -eq 0 ]; then
        echo -e "${GREEN}🎉 Version $VERSION released successfully!${NC}"
        echo -e "${CYAN}🔗 Your plugin is now available at: https://wordpress.org/plugins/trust-badges/${NC}"
    else
        echo -e "${RED}❌ Failed to commit version $VERSION.${NC}"
        exit 1
    fi
    exit 0
fi

echo -e "${RED}❌ Unknown change type. Please choose 'assets', 'tag', '1', or '2'.${NC}"
exit 1 