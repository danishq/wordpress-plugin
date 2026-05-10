import CircularProgress from "@mui/material/CircularProgress";
import PropTypes from "prop-types";
import styles from "./Progress.module.scss";

const Progress = ({ button }) => {
  return (
    <>
      {button ? (
        <CircularProgress className="loader-btn" />
      ) : (
        <div className={styles.container}>
          <CircularProgress className="loader-brand" />
        </div>
      )}
    </>
  );
};

Progress.propTypes = {
  button: PropTypes.bool,
};

Progress.defaultProps = {
  button: false,
};

export default Progress;
