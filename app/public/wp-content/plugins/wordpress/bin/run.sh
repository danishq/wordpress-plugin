#!/bin/bash

# Check if the correct number of arguments are provided
if [ "$#" -ne 3 ]; then
    echo "Usage: $0 input_file output_php_file version"
    exit 1
fi

# Assign arguments to variables
input_file="$1"
output_file="$2"
version="$3"

# Debugging: Show input variables
echo "Input file: $input_file"
echo "Output file: $output_file"
echo "Version: $version"

# Check if input file exists
if [ ! -f "$input_file" ]; then
    echo "Error: Input file '$input_file' not found."
    exit 1
fi

sed "s/{{{VERSION}}}/$version/g" "$input_file" > "$output_file"


echo "Final replacement complete. Modified content saved to '$output_file'."
