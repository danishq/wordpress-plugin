import CircularProgress from "@mui/material/CircularProgress";
import InputAdornment from "@mui/material/InputAdornment";
import TextField from "@mui/material/TextField";
import React, { useEffect, useState } from "react";
import { ReactComponent as IconCross } from "../../assets/icons/icon-cross.svg";
import { ReactComponent as IconSearch } from "../../assets/icons/icon-search.svg";

const SEARCH_LOADER = "SEARCH_LOADER";
export const toggleSearchLoader = (val) => ({
  type: SEARCH_LOADER,
  payload: val,
});

const SearchBar = ({ onSearch, onClear, isTableLoad = true }) => {
  const [searchValue, setSearchValue] = useState("");
  const [isLoading, setIsLoading] = useState(false);

  useEffect(() => {
    if (searchValue) {
      if (isTableLoad) {
        setIsLoading(true);
      }
      const delayDebounceFn = setTimeout(() => {
        Promise.resolve(onSearch(searchValue)).finally(() =>
          setIsLoading(false),
        );
      }, 300);

      return () => clearTimeout(delayDebounceFn);
    } else {
      setIsLoading(false);
    }
  }, [searchValue, onSearch]);

  const handleClearSearch = () => {
    setSearchValue("");
    setIsLoading(false);
    onClear();
  };

  const handleInputChange = (e) => {
    const value = e.target.value;
    setSearchValue(value);

    if (!value.trim()) {
      handleClearSearch();
    }
  };

  return (
    <TextField
      placeholder="Search..."
      value={searchValue}
      onChange={handleInputChange}
      InputProps={{
        sx: {
          padding: "8px 12px",
        },
        startAdornment: (
          <InputAdornment position="start">
            <IconSearch className="icon20" />
          </InputAdornment>
        ),
        endAdornment: (
          <InputAdornment position="end">
            {isLoading ? (
              <CircularProgress
                size={24}
                style={{ position: "absolute", right: "4px" }}
              />
            ) : searchValue ? (
              <button className="btn common-btn" onClick={handleClearSearch}>
                <IconCross className="icon20" />
              </button>
            ) : null}
          </InputAdornment>
        ),
      }}
      className="search-box"
    />
  );
};

export default SearchBar;
