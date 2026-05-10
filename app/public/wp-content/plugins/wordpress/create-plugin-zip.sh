#!/bin/bash

# Define zip filename
ZIP_FILE="adflipr.zip"

# Files/folders to zip
items_to_zip=("assets" "build" "includes" "adflipr.php" "README.md" "uninstall.php")
missing_items=()

# Check if the items exist
for item in "${items_to_zip[@]}"; do
    if [ ! -e "$item" ]; then
        missing_items+=("$item")
    fi
done

echo "----------------------------------------"
echo "Creating zip file..."
echo "----------------------------------------"

# Notify about missing items
if [ ${#missing_items[@]} -gt 0 ]; then
    echo "Warning: The following files/folders do not exist:"
    echo "----------------------------------------"
    for item in "${missing_items[@]}"; do
        echo "- $item"
    done
    echo "----------------------------------------"
    echo "Please check the files/folders and try again."
    echo "----------------------------------------"
    exit 1
fi


# Create the zip file with only the specified files/folders
# The -r flag ensures directories are processed recursively
zip -r "$ZIP_FILE" "${items_to_zip[@]}" 2>/dev/null

# Check if zip was successful
if [ $? -eq 0 ]; then
    echo "----------------------------------------"
    echo "Successfully created $ZIP_FILE with the following files/folders:"
    echo "----------------------------------------"
    zip -sf "$ZIP_FILE"
    echo "----------------------------------------"
else
    echo "----------------------------------------"
    echo "Failed to create zip file."
    echo "----------------------------------------"
    exit 1
fi
