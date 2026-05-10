import React from "react";

const ShimmerTable = () => {
  const rows = Array.from({ length: 10 });

  return (
    <div className="shimmer-container">
      <table className="shimmer-table">
        <thead>
          <tr>
            {Array.from({ length: 6 }).map((_, index) => (
              <th key={index}>
                <div className="shimmer shimmer-header" />
              </th>
            ))}
          </tr>
        </thead>
        <tbody>
          {rows.map((_, rowIndex) => (
            <tr
              key={rowIndex}
              className={rowIndex % 2 === 0 ? "shimmer-row" : "shimmer-row-alt"}
            >
              {Array.from({ length: 6 }).map((_, colIndex) => (
                <td key={colIndex}>
                  <div
                    className={`shimmer shimmer-body shimmer-col-${
                      colIndex + 1
                    }`}
                  />
                </td>
              ))}
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  );
};

export default ShimmerTable;
