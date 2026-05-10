import { Chip, IconButton } from "@mui/material";
import Box from "@mui/material/Box";
import Button from "@mui/material/Button";
import Checkbox from "@mui/material/Checkbox";
import Stack from "@mui/material/Stack";
import Table from "@mui/material/Table";
import TableBody from "@mui/material/TableBody";
import TableCell from "@mui/material/TableCell";
import TableContainer from "@mui/material/TableContainer";
import TableHead from "@mui/material/TableHead";
import TableRow from "@mui/material/TableRow";
import Typography from "@mui/material/Typography";
import classNames from "classnames";
import { Fragment, useState } from "react";
import iconCross from "../../assets/icons/icon-cross.svg";
import iconMore from "../../assets/icons/icon-more.svg";
import ImageNoSearch from "../../assets/images/no-search-results.png";
import Icon from "../Icon";
import CustomMenu from "../Menu";
import SearchBar from "../SearchBar";
import ShimmerTable from "./ShimmerTable";
import "./Table.css";

const TableToolBar = ({
  selectedCount,
  actions = [],
  bulkActions = [],
  onClearAllClick,
}) => {
  const actionsToRender = selectedCount > 0 ? bulkActions : actions;
  return (
    <Stack direction="row" spacing={1} sx={{ mb: 2 }} alignItems="center">
      {selectedCount > 0 ? (
        <>
          <Typography
            variant="subtitle2"
            component={"span"}
            className="table-top"
          >
            {selectedCount} items
            <span> selected in the list</span>
          </Typography>
          <button
            className="btn no-border"
            sx={{ mr: "12px" }}
            onClick={onClearAllClick}
            style={{ textTransform: "capitalize" }}
          >
            <img
              src={iconCross}
              alt="cross"
              className="icon16"
              style={{ marginRight: "4px", height: "16px", width: "16px" }}
            />
            <span>Clear All</span>
          </button>
          <div className="table-toolbar-seperator"></div>
        </>
      ) : null}
      {actionsToRender
        .filter((action) => !action.disabled) // Exclude actions with disabled: true
        .map((action, index) => {
          const {
            label,
            onClick,
            endIcon,
            startIcon,
            classes,
            isSearch,
            onSearch,
            onClear,
          } = action;

          return (
            <Fragment key={index}>
              {isSearch && <SearchBar onSearch={onSearch} onClear={onClear} />}
              {!isSearch && (
                <Button
                  className={classes}
                  onClick={(event) => onClick(event)}
                  endIcon={endIcon}
                  startIcon={startIcon}
                  sx={{ textTransform: "none !important" }}
                >
                  {label}
                </Button>
              )}
            </Fragment>
          );
        })}
    </Stack>
  );
};

const EnhancedTableHead = ({
  headers = [],
  onSelectAllClick,
  numSelected = 0,
  rowCount = 0,
  noCheckBox,
  showMoreOptions,
  ifEmpty,
}) => {
  return (
    <TableHead>
      <TableRow>
        {!noCheckBox && (
          <EnhancedTableCell
            sx={{ paddingLeft: "0px !important" }}
            sticky
            leftPosition={0}
          >
            <div
              style={{
                display: "flex",
                alignItems: "center",
                gap: "8px",
                height: ifEmpty ? "20px" : "",
              }}
            >
              <Checkbox
                color="primary"
                checked={rowCount > 0 && numSelected === rowCount}
                onChange={onSelectAllClick}
                style={{ display: ifEmpty ? "none" : "" }}
              />
            </div>
          </EnhancedTableCell>
        )}
        {showMoreOptions && (
          <EnhancedTableCell
            sx={{ paddingLeft: "0px !important" }}
            sticky
            leftPosition={0}
          >
            <div style={{ width: 24 }} />
          </EnhancedTableCell>
        )}
        {headers.map((header, index) => (
          <EnhancedTableCell
            key={header.key}
            className={classNames("TableHeader", header.customClass)}
            sticky={header.sticky}
            leftPosition={index === 0 ? 76 : index * 140 + 100}
          >
            {header.label}
          </EnhancedTableCell>
        ))}
      </TableRow>
    </TableHead>
  );
};

const EnhancedTableCell = ({
  children,
  onClick,
  sticky,
  leftPosition,
  ...rest
}) => {
  let tableCellProps = {
    ...rest,
    sx: {
      borderRight: 1,
      borderColor: ({ palette }) => palette.grey[200],
      cursor: onClick ? "pointer" : "default",
      position: sticky ? "sticky" : "static",
      left: sticky ? leftPosition : "auto",
      zIndex: sticky ? 2 : "auto",
      backgroundColor: sticky ? "#fff" : "inherit",
    },
  };
  if (onClick) {
    tableCellProps = { ...tableCellProps, onClick };
  }
  return <TableCell {...tableCellProps}>{children}</TableCell>;
};

function extractLabels(values) {
  // Check if values is an array
  if (Array.isArray(values)) {
    return values.map((value) => {
      // If the value is in the 'id:label' format, return only the label (part after ':')
      if (typeof value === "string" && value.includes(":")) {
        return value.split(":")[1]; // Return the label part
      }
      return value; // If not in the correct format, return the value as it is
    });
  }
  // If input is not an array, return the value as is (this is an edge case if you pass a single value)
  return values;
}

const FilterChips = ({ filters }) => (
  <>
    {filters.map(({ attribute, condition, value }, index) => (
      <Fragment key={index}>
        <Chip
          label={`${attribute} ${condition} ${extractLabels(value)}`}
          size="small"
          sx={{ border: "1px solid #DFDDED", backgroundColor: "#F9FAFB" }}
        />
        {index < filters.length - 1 && (
          <Chip
            label="AND"
            sx={{ backgroundColor: (theme) => theme.palette.yellow?.light }}
            size="small"
          />
        )}
      </Fragment>
    ))}
  </>
);

const AppliedFiltersDisplay = ({ appliedFilters }) => {
  const validAppliedFilters = appliedFilters.filter((filterBlock) =>
    filterBlock.some(({ attribute, condition }) => attribute && condition),
  );

  return (
    <>
      {validAppliedFilters.map((filterBlock, blockIndex) => (
        <Fragment key={blockIndex}>
          <FilterChips filters={filterBlock} />
          {blockIndex < validAppliedFilters.length - 1 && (
            <Chip
              label="OR"
              sx={{ backgroundColor: (theme) => theme.palette.yellow?.light }}
              size="small"
            />
          )}
        </Fragment>
      ))}
    </>
  );
};

const TableContent = ({
  headers,
  rows,
  selected,
  onSelectAllClick,
  onSelectRow,
  noCheckBox,
  moreOptions,
  handleMoreClick,
  setRowSelected,
  ifEmpty,
  showMoreOptions,
}) => {
  return (
    <TableContainer>
      <Table>
        <EnhancedTableHead
          headers={headers}
          onSelectAllClick={onSelectAllClick}
          numSelected={selected.length}
          rowCount={rows.length}
          noCheckBox={noCheckBox}
          showMoreOptions={showMoreOptions}
          ifEmpty={ifEmpty}
        />
        <TableBody>
          {ifEmpty ? (
            <TableRow>
              <EnhancedTableCell
                colSpan={
                  headers.length +
                  (noCheckBox ? 0 : 1) +
                  (showMoreOptions ? 1 : 0)
                }
                align="center"
              >
                <div className="empty-table-state">
                  <img src={ImageNoSearch} alt="No result" />
                  <span>No data available</span>
                </div>
              </EnhancedTableCell>
            </TableRow>
          ) : (
            rows.map((row, index) => (
              <TableRow
                key={row.id}
                sx={{
                  cursor: "pointer",
                  backgroundColor: () =>
                    index % 2 === 0 ? "#f9fafb" : "#ffffff",
                }}
              >
                {!noCheckBox && (
                  <EnhancedTableCell
                    className="firstCol"
                    sticky
                    leftPosition={0}
                  >
                    <div style={{ display: "flex", alignItems: "center" }}>
                      <Checkbox
                        color="primary"
                        onClick={() => onSelectRow(row.id)}
                        checked={selected.indexOf(row.id) !== -1}
                      />
                      <img
                        src={iconMore}
                        alt="icon-more"
                        onClick={(event) => {
                          handleMoreClick(event);
                          setRowSelected(row);
                        }}
                      />
                    </div>
                  </EnhancedTableCell>
                )}

                {showMoreOptions && (
                  <EnhancedTableCell className="firstCol">
                    {(!row.contactFieldType ||
                      row.contactFieldType !== "SYSTEM") && (
                      <div style={{ display: "flex", alignItems: "center" }}>
                        <img
                          src={iconMore}
                          alt="icon-more"
                          onClick={(event) => {
                            handleMoreClick(event);
                            setRowSelected(row);
                          }}
                        />
                      </div>
                    )}
                  </EnhancedTableCell>
                )}

                {headers.map(
                  ({ numeric, key, render, onClick, sticky }, colIndex) => {
                    const RenderComp = render;
                    return (
                      <EnhancedTableCell
                        onClick={onClick ? () => onClick(row) : ""}
                        align={numeric ? "right" : "left"}
                        key={key}
                        sticky={sticky}
                        leftPosition={
                          colIndex === 0 ? 76 : colIndex * 140 + 100
                        }
                      >
                        {RenderComp ? <RenderComp {...row} /> : row[key] || "_"}
                      </EnhancedTableCell>
                    );
                  },
                )}
              </TableRow>
            ))
          )}
        </TableBody>
      </Table>
    </TableContainer>
  );
};

const TableComp = ({
  headers = [],
  isLoading = false,
  rows = [],
  noCheckBox = false,
  moreOptions,
  actions = [],
  bulkActions = [],
  rowActions = [],
  appliedFilters = [],
  onFilterEdit,
  ifEmpty,
  selected = [],
  setSelected,
  showMoreOptions = false, // New prop for Fields table
}) => {
  const [rowSelected, setRowSelected] = useState([]);
  const isSearchLoading = false;
  const [anchorEl, setAnchorEl] = useState(null);

  const onSelectAllClick = (event) => {
    if (event.target.checked) {
      const newSelected = rows?.map((n) => n.id);
      setSelected(newSelected);
      return;
    }
    setSelected([]);
  };

  const handleMoreClick = (event) => {
    setAnchorEl(event.currentTarget);
  };

  const onClearAllClick = () => {
    setSelected([]);
  };

  const onSelectRow = (id) => {
    let newSelected = [];
    const isSelected = selected.some((selectedId) => selectedId === id);
    if (isSelected) {
      newSelected = selected.filter((selectedId) => selectedId !== id);
    } else {
      newSelected = selected.concat(id);
    }
    setSelected(newSelected);
  };

  return (
    <>
      <TableToolBar
        selectedCount={selected.length}
        onClearAllClick={onClearAllClick}
        actions={actions}
        bulkActions={bulkActions.map((bulkAction) => ({
          ...bulkAction,
          onClick: (event) => {
            bulkAction.onClick(selected, event);
          },
        }))}
      />

      {appliedFilters.length > 0 && (
        <Stack
          mb={2}
          bgcolor="#F9FAFB"
          minHeight="48px"
          border="1px solid #DFDDED"
          borderRadius="12px"
          padding={1.5}
          alignItems="center"
          flexDirection="row"
          flexWrap="wrap"
          gap={1.5}
          position="relative"
        >
          Filter
          {<AppliedFiltersDisplay appliedFilters={appliedFilters} />}
          <IconButton
            onClick={onFilterEdit}
            sx={{ position: "absolute", right: 12 }}
          >
            <Icon type="icon-edit" />
          </IconButton>
        </Stack>
      )}

      <Box
        sx={{
          borderRadius: 2,
          border: 1,
          borderColor: ({ palette }) => palette.grey[200],
        }}
      >
        {isLoading || isSearchLoading ? (
          <ShimmerTable />
        ) : (
          <TableContent
            headers={headers}
            rows={rows}
            selected={selected}
            onSelectAllClick={onSelectAllClick}
            onSelectRow={onSelectRow}
            noCheckBox={noCheckBox}
            moreOptions={moreOptions}
            handleMoreClick={handleMoreClick}
            setRowSelected={setRowSelected}
            ifEmpty={ifEmpty}
            showMoreOptions={showMoreOptions}
          />
        )}

        <CustomMenu
          onSelect={(value) => {
            const { onClick } =
              rowActions.find((rowAction) => rowAction.value === value) || {};
            onClick?.(rowSelected);
            setAnchorEl(null);
          }}
          options={rowActions
            .filter((action) => !action.disabled) // Exclude disabled options
            .map((action) => ({
              ...action,
              label:
                typeof action.label === "function"
                  ? action.label(rowSelected)
                  : action.label,
              icon:
                typeof action.icon === "function"
                  ? action.icon(rowSelected)
                  : action.icon,
            }))}
          open={!!anchorEl && rowActions.some((action) => !action.disabled)} // Open only if there are non-disabled options
          onClose={() => {
            setAnchorEl(null);
          }}
          horizontalAlignment="bottom"
          verticalAlighnment="bottom"
          anchorEl={anchorEl}
        />
      </Box>
    </>
  );
};

export default TableComp;
