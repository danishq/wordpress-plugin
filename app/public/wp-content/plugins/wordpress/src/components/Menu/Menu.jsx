import ListItemText from "@mui/material/ListItemText";
import Menu from "@mui/material/Menu";
import MenuItem from "@mui/material/MenuItem";
import { useEffect, useState } from "react";
import Icon from "../Icon";

const CustomMenu = ({
  anchorEl,
  onSelect,
  options = [],
  onClose,
  verticalAlighnment = "bottom",
  horizontalAlignment = "left",
  inputBox, // The element that triggered the menu
}) => {
  const [menuStyles, setMenuStyles] = useState({});

  useEffect(() => {
    if (anchorEl && inputBox) {
      // Get the width of the input element (anchorEl)
      const { width } = anchorEl.getBoundingClientRect();
      setMenuStyles({ width: `${width}px` }); // Set the width of the menu
    }
  }, [anchorEl, inputBox]); // Ensure this effect runs when anchorEl changes

  // Don't render the menu if there are no options
  if (!options || options.length === 0) return null;

  return (
    <Menu
      anchorEl={anchorEl}
      open={!!anchorEl}
      onClose={onClose}
      anchorOrigin={{
        vertical: verticalAlighnment,
        horizontal: horizontalAlignment,
      }}
      className="dropdown"
      PaperProps={{
        style: menuStyles, // Apply dynamic styles
      }}
    >
      {options.map(({ icon, label, value }, index) => (
        <MenuItem
          key={index}
          sx={{ py: 1, px: 2, height: "32px" }}
          onClick={() => onSelect(value, label)}
        >
          {icon && <Icon type={icon} />}
          <ListItemText>{label}</ListItemText>
        </MenuItem>
      ))}
    </Menu>
  );
};

export default CustomMenu;
