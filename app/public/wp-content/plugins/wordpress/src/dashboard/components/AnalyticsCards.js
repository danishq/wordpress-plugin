import React from "react";
import getSymbolFromCurrency from "currency-symbol-map";
import Progress from "../../components/Progress";
import StyledNumber from "../../utils/decimal-util";

const AnalyticsCard = ({
  title,
  centralValue,
  centralLabel,
  lastMonth,
  today,
  currency,
  isLoading,
}) => {
  return (
    <div className="analytics-card">
      {/* Title */}
      <h3 className="analytics-title">{title}</h3>

      {/* Central Value Section */}
      <div className="central-value">
        <span className="central-number">
          {Number(centralValue) !== 0 ? getSymbolFromCurrency(currency) : ""}
          <StyledNumber
            num={centralValue}
            wholeFontSize="56px"
            decimalFontSize="24px"
          />
        </span>
        <p className="central-label">{centralLabel}</p>
      </div>

      {/* Bottom Values */}
      {isLoading ? (
        <Progress />
      ) : (
        <div className="bottom-values">
          <div className="bottom-value">
            <span>
              {Number(lastMonth) !== 0 ? getSymbolFromCurrency(currency) : ""}
              <StyledNumber
                num={lastMonth}
                wholeFontSize="18px"
                decimalFontSize="12px"
              />
            </span>
            <p>Last Month</p>
          </div>
          <div className="bottom-value">
            <span>
              {Number(today) !== 0 ? getSymbolFromCurrency(currency) : ""}
              <StyledNumber
                num={today}
                wholeFontSize="18px"
                decimalFontSize="12px"
              />
            </span>
            <p>Today</p>
          </div>
        </div>
      )}
    </div>
  );
};

export default AnalyticsCard;
