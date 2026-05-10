import classNames from "classnames";
import formatStringToTitleCase from "../../utils/string-utils";
const Status = ({ status }) => {
  return (
    <>
      <div className={classNames("status", status)}>
        {" "}
        {formatStringToTitleCase(status)}
      </div>
    </>
  );
};

export default Status;
