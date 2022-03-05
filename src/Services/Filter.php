<?php

namespace Joy\VoyagerApi\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use TCG\Voyager\Models\DataRow;
use TCG\Voyager\Models\DataType;

class Filter
{
    /**
     * Handle
     *
     * @param Builder|QueryBuilder $query Query
     */
    public function handle(
        $query,
        $keyword,
        DataRow $row,
        DataType $dataType,
        Request $request
    ): void {
        if (!$keyword) {
            return;
        }

        // if (isset($row->details->view)) {
        // one must override the filter as well
        // }

        // Note:: you can override filter for each column by adding scope{field}
        // i.e. for age column you can add scopeAge and for created_at you can add scopeCreatedAt
        if (modelHasScope($dataType->model_name, $row->field)) {
            $query->scopes([
                Str::camel($row->field) => [$keyword],
            ]);
            return;
        }

        // You can disable filters by row type
        if (in_array($row->type, config('joy-voyager-api.filters.hidden', ['hidden']))) {
            return;
        }

        // You can disable filters by row field for different data types
        if (in_array($row->field, config('joy-voyager-api.' . $dataType->slug . '.filters.hidden', ['hidden']))) {
            return;
        }

        if ($row->type == 'select_dropdown' || $row->type == 'radio_btn') {
            $this->filterSelectDropdown(
                $query,
                $keyword,
                $row,
                $dataType,
                $request
            );
            return;
        }

        if ($row->type == 'date' || $row->type == 'timestamp') {
            $this->filterDate(
                $query,
                $keyword,
                $row,
                $dataType,
                $request
            );
            return;
        }

        if (method_exists($this, 'filter' . Str::studly($row->type))) {
            $this->{'filter' . Str::studly($row->type)}(
                $query,
                $keyword,
                $row,
                $dataType,
                $request
            );
            return;
        }

        $this->filterDefault(
            $query,
            $keyword,
            $row,
            $dataType,
            $request
        );
        return;
    }

    /**
     * Filter image
     *
     * @param Builder|QueryBuilder $query   Query
     * @param mixed                $keyword Keyword
     */
    protected function filterImage(
        $query,
        $keyword,
        DataRow $row,
        DataType $dataType,
        Request $request
    ): void {
        // @TODO Not implemented yet. Probably radio check if field is null or not
    }

    /**
     * Filter relationship
     *
     * @param Builder|QueryBuilder $query   Query
     * @param mixed                $keyword Keyword
     */
    protected function filterRelationship(
        $query,
        $keyword,
        DataRow $row,
        DataType $dataType,
        Request $request
    ): void {
        // @TODO Not implemented yet.
    }

    /**
     * Filter select multiple
     *
     * @param Builder|QueryBuilder $query   Query
     * @param mixed                $keyword Keyword
     */
    protected function filterSelectMultiple(
        $query,
        $keyword,
        DataRow $row,
        DataType $dataType,
        Request $request
    ): void {
        // @TODO Not implemented yet.
    }

    /**
     * Filter multiple checkbox
     *
     * @param Builder|QueryBuilder $query   Query
     * @param mixed                $keyword Keyword
     */
    protected function filterMultipleCheckbox(
        $query,
        $keyword,
        DataRow $row,
        DataType $dataType,
        Request $request
    ): void {
        // @TODO Not implemented yet.
    }

    /**
     * Filter select dropdown
     *
     * @param Builder|QueryBuilder $query   Query
     * @param mixed                $keyword Keyword
     */
    protected function filterSelectDropdown(
        $query,
        $keyword,
        DataRow $row,
        DataType $dataType,
        Request $request
    ): void {
        $query->whereIn($row->field, $keyword);
    }

    /**
     * Filter date
     *
     * @param Builder|QueryBuilder $query   Query
     * @param mixed                $keyword Keyword
     */
    protected function filterDate(
        $query,
        $keyword,
        DataRow $row,
        DataType $dataType,
        Request $request
    ): void {
        // @TODO Not implemented yet. Must be range
    }

    /**
     * Filter time
     *
     * @param Builder|QueryBuilder $query   Query
     * @param mixed                $keyword Keyword
     */
    protected function filterTime(
        $query,
        $keyword,
        DataRow $row,
        DataType $dataType,
        Request $request
    ): void {
        // @TODO Not implemented yet. Must be range
    }

    /**
     * Filter checkbox
     *
     * @param Builder|QueryBuilder $query   Query
     * @param mixed                $keyword Keyword
     */
    protected function filterCheckbox(
        $query,
        $keyword,
        DataRow $row,
        DataType $dataType,
        Request $request
    ): void {
        // @TODO Not implemented yet.
    }

    /**
     * Filter color
     *
     * @param Builder|QueryBuilder $query   Query
     * @param mixed                $keyword Keyword
     */
    protected function filterColor(
        $query,
        $keyword,
        DataRow $row,
        DataType $dataType,
        Request $request
    ): void {
        $query->where($row->field, $keyword);
    }

    /**
     * Filter text
     *
     * @param Builder|QueryBuilder $query   Query
     * @param mixed                $keyword Keyword
     */
    protected function filterText(
        $query,
        $keyword,
        DataRow $row,
        DataType $dataType,
        Request $request
    ): void {
        $this->filterDefault(
            $query,
            $keyword,
            $row,
            $dataType,
            $request
        );
    }

    /**
     * Filter number
     *
     * @param Builder|QueryBuilder $query   Query
     * @param mixed                $keyword Keyword
     */
    protected function filterNumber(
        $query,
        $keyword,
        DataRow $row,
        DataType $dataType,
        Request $request
    ): void {
        // @TODO must check range
    }

    /**
     * Filter text area
     *
     * @param Builder|QueryBuilder $query   Query
     * @param mixed                $keyword Keyword
     */
    protected function filterTextArea(
        $query,
        $keyword,
        DataRow $row,
        DataType $dataType,
        Request $request
    ): void {
        $this->filterDefaultTranslated(
            $query,
            $keyword,
            $row,
            $dataType,
            $request
        );
    }

    /**
     * Filter file
     *
     * @param Builder|QueryBuilder $query   Query
     * @param mixed                $keyword Keyword
     */
    protected function filterFile(
        $query,
        $keyword,
        DataRow $row,
        DataType $dataType,
        Request $request
    ): void {
        // @TODO Not implemented yet.
    }

    /**
     * Filter rich text box
     *
     * @param Builder|QueryBuilder $query   Query
     * @param mixed                $keyword Keyword
     */
    protected function filterRichTextBox(
        $query,
        $keyword,
        DataRow $row,
        DataType $dataType,
        Request $request
    ): void {
        $this->filterDefaultTranslated(
            $query,
            $keyword,
            $row,
            $dataType,
            $request
        );
    }

    /**
     * Filter coordinates
     *
     * @param Builder|QueryBuilder $query   Query
     * @param mixed                $keyword Keyword
     */
    protected function filterCoordinates(
        $query,
        $keyword,
        DataRow $row,
        DataType $dataType,
        Request $request
    ): void {
        // @TODO Not implemented yet.
    }

    /**
     * Filter multiple images
     *
     * @param Builder|QueryBuilder $query   Query
     * @param mixed                $keyword Keyword
     */
    protected function filterMultipleImages(
        $query,
        $keyword,
        DataRow $row,
        DataType $dataType,
        Request $request
    ): void {
        // @TODO Not implemented yet.
    }

    /**
     * Filter media picker
     *
     * @param Builder|QueryBuilder $query   Query
     * @param mixed                $keyword Keyword
     */
    protected function filterMediaPicker(
        $query,
        $keyword,
        DataRow $row,
        DataType $dataType,
        Request $request
    ): void {
        // @TODO Not implemented yet.
    }

    /**
     * Filter default
     *
     * @param Builder|QueryBuilder $query   Query
     * @param mixed                $keyword Keyword
     */
    protected function filterDefault(
        $query,
        $keyword,
        DataRow $row,
        DataType $dataType,
        Request $request
    ): void {
        $this->filterDefaultTranslated(
            $query,
            $keyword,
            $row,
            $dataType,
            $request
        );
    }

    /**
     * Filter default translated
     *
     * @param Builder|QueryBuilder $query   Query
     * @param mixed                $keyword Keyword
     */
    protected function filterDefaultTranslated(
        $query,
        $keyword,
        DataRow $row,
        DataType $dataType,
        Request $request
    ): void {
        $model = app($dataType->model_name);

        if (!is_field_translatable($model, $row)) {
            $query->where($row->field, 'LIKE', '%' . $keyword . '%');
            return;
        }

        // @FIXME if needs to filter in translations as well
        $query->where($row->field, 'LIKE', '%' . $keyword . '%');
    }
}
