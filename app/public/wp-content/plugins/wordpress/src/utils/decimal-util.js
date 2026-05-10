import React from 'react';

const StyledNumber = ({
  num,
  wholeFontSize = '16px',
  decimalFontSize = '12px',
  decimalPlaces = 2,
}) => {
  if (num === null || num === undefined) {
    return 0; // Return nothing for invalid input
  }

  // Convert to string and extract numeric part and suffix
  const str = num.toString();
  const numericMatch = str.match(/^([+-]?\d*\.?\d+)/);

  if (!numericMatch) {
    return num; // Return original if no numeric part found
  }

  const numericPart = numericMatch[1];
  const suffix = str.substring(numericMatch[0].length); // Get everything after the number

  // Convert numeric part to number and check if it has decimal part
  const number = Number(numericPart);
  const hasDecimal = number % 1 !== 0;

  // Only format to decimal places if the number actually has decimals
  const formattedNum = hasDecimal
    ? number.toFixed(decimalPlaces)
    : number.toString();
  const [whole, decimal] = formattedNum.split('.');

  return (
    <span className="styled-number">
      <span style={{ fontSize: wholeFontSize }}>{whole}</span>
      {decimal !== undefined && hasDecimal && (
        <>
          <span style={{ fontSize: wholeFontSize }}>.</span>
          <span style={{ fontSize: decimalFontSize }}>{decimal}</span>
        </>
      )}
      {suffix && <span style={{ fontSize: wholeFontSize }}>{suffix}</span>}
    </span>
  );
};

export default StyledNumber;
