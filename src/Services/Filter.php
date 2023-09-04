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

        if ($dataType->model_name && $row->field == (app($dataType->model_name))->getKeyName()) {
            $this->filterByKey(
                $query,
                $keyword,
                $row,
                $dataType,
                $request
            );
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
        $query->when($keyword === '1' || $keyword === 'Yes', function ($query) use ($row, $keyword) {
            $query->where(function ($query) use ($row, $keyword) {
                $query
                    ->whereNotNull($row->field)
                    ->where($row->field, '!=', config('voyager.user.default_avatar', 'users/default.png'));
            });
        })->when($keyword === '0' || $keyword === 'No', function ($query) use ($row, $keyword) {
            $query->where(function ($query) use ($row, $keyword) {
                $query
                    ->whereNull($row->field)
                    ->orWhere($row->field, config('voyager.user.default_avatar', 'users/default.png'));
            });
        });
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
        if (method_exists($this, 'filterRelationship' . Str::studly($row->details->type))) {
            $this->{'filterRelationship' . Str::studly($row->details->type)}(
                $query,
                $keyword,
                $row,
                $dataType,
                $request
            );
            return;
        }
    }

    /**
     * Filter belongsTo relationship
     *
     * @param Builder|QueryBuilder $query   Query
     * @param mixed                $keyword Keyword
     */
    protected function filterRelationshipBelongsTo(
        $query,
        $keyword,
        DataRow $row,
        DataType $dataType,
        Request $request
    ): void {
        $keywords = explode(',', $keyword);
        $query->whereIn($row->details->column, $keywords);
    }

    /**
     * Filter hasOne relationship
     *
     * @param Builder|QueryBuilder $query   Query
     * @param mixed                $keyword Keyword
     */
    protected function filterRelationshipHasOne(
        $query,
        $keyword,
        DataRow $row,
        DataType $dataType,
        Request $request
    ): void {
        // @TODO Not implemented yet.
    }

    /**
     * Filter hasMany relationship
     *
     * @param Builder|QueryBuilder $query   Query
     * @param mixed                $keyword Keyword
     */
    protected function filterRelationshipHasMany(
        $query,
        $keyword,
        DataRow $row,
        DataType $dataType,
        Request $request
    ): void {
        // @TODO Not implemented yet.
    }

    /**
     * Filter belongsToMany relationship
     *
     * @param Builder|QueryBuilder $query   Query
     * @param mixed                $keyword Keyword
     */
    protected function filterRelationshipBelongsToMany(
        $query,
        $keyword,
        DataRow $row,
        DataType $dataType,
        Request $request
    ): void {
        $keywords              = explode(',', $keyword);
        $model                 = $query->getModel();
        $options               = $row->details;
        $belongsToManyRelation = $model->belongsToMany($options->model, $options->pivot_table, $options->foreign_pivot_key ?? null, $options->related_pivot_key ?? null, $options->parent_key ?? null, $options->key);

        $query->whereExists(function ($query) use ($model, $belongsToManyRelation, $options, $keywords) {
            $query->from($options->pivot_table)
                ->whereColumn($options->pivot_table . '.' . $belongsToManyRelation->getForeignPivotKeyName(), $model->getTable() . '.' . $model->getKeyName())
                ->whereIn($belongsToManyRelation->getRelatedPivotKeyName(), $keywords);
        });
    }

    /**
     * Filter morphTo relationship
     *
     * @param Builder|QueryBuilder $query   Query
     * @param mixed                $keyword Keyword
     */
    protected function filterRelationshipMorphTo(
        $query,
        $keyword,
        DataRow $row,
        DataType $dataType,
        Request $request
    ): void {
        $peices            = explode(',,', $keyword);
        $morphToType       = $peices[0] ?? null;
        $morphToIdKeyword  = $peices[1] ?? null;
        $morphToIdKeywords = $morphToIdKeyword ? explode(',', $morphToIdKeyword) : null;
        $options           = $row->details;
        $typeColumn        = $options->type_column;
        $column            = $options->column;
        $types             = $options->types ?? [];

        $query->when(
            $morphToType && in_array($morphToType, collect($types)->pluck('model')->toArray()),
            function ($query) use ($typeColumn, $morphToType) {
                $query->where($typeColumn, $morphToType);
            }
        )->when(
            $morphToIdKeywords,
            function ($query) use ($column, $morphToIdKeywords) {
                $query->whereIn($column, $morphToIdKeywords);
            }
        );
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
        $keywords = explode(',', $keyword);
        $query->where(function ($query) use ($row, $keywords) {
            foreach ($keywords as $keyword) {
                $query->orWhere(function ($query) use ($row, $keyword) {
                    $query->whereJsonContains($row->field . '->' . $keyword, $keyword);
                });
            }
        });
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
        $keywords = explode(',', $keyword);
        $query->where(function ($query) use ($row, $keywords) {
            foreach ($keywords as $keyword) {
                $query->orWhere(function ($query) use ($row, $keyword) {
                    $query->whereJsonContains($row->field . '->' . $keyword, $keyword);
                });
            }
        });
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
        $keywords = explode(',', $keyword);
        $query->whereIn($row->field, $keywords);
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
        $keywords = explode(',', $keyword);
        $from     = $keywords[0] ?? null;
        $to       = $keywords[1] ?? null;
        if ($from) {
            $from = safeCarbonParse($from);
        }
        if ($to) {
            $to = safeCarbonParse($to);
        }

        if (count($keywords) === 1 && $from && isValidCarbon($keyword)) {
            $query->whereDate($row->field, $from->format('Y-m-d'));
            return;
        }

        if (count($keywords) === 2) {
            $query->when($from && $to, function ($query) use ($row, $from, $to) {
                $query->whereBetween($row->field, [$from->format('Y-m-d H:i'), $to->format('Y-m-d H:i')]);
            }, function ($query) use ($row, $from, $to) {
                $query->when($from, function ($query) use ($row, $from) {
                    $query->where($row->field, '>=', $from->format('Y-m-d H:i'));
                })->when($to, function ($query) use ($row, $to) {
                    $query->where($row->field, '<=', $to->format('Y-m-d H:i'));
                });
            });
            return;
        }
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
        $options = $row->details;
        $query->when($keyword === '1' || $keyword === 'Yes', function ($query) use ($row, $options) {
            $query->where($row->field, '1')->whereNotNull($row->field);
        })->when($keyword === '0' || $keyword === 'No', function ($query) use ($row, $options) {
            $query->where($row->field, '0')->orWhereNull($row->field);
        });
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
        $keywords = explode(',', $keyword);
        $from     = $keywords[0] ?? null;
        $to       = $keywords[1] ?? null;

        if (count($keywords) === 1 && $from) {
            $query->where($row->field, $from);
            return;
        }

        if (count($keywords) === 2) {
            $query->when($from && $to, function ($query) use ($row, $from, $to) {
                $query->whereBetween($row->field, [$from, $to]);
            }, function ($query) use ($row, $from, $to) {
                $query->when($from, function ($query) use ($row, $from) {
                    $query->where($row->field, '>=', $from);
                })->when($to, function ($query) use ($row, $to) {
                    $query->where($row->field, '<=', $to);
                });
            });
            return;
        }
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
        $query->when($keyword === '1' || $keyword === 'Yes', function ($query) use ($row) {
            $query->where(function ($query) use ($row) {
                $query
                    ->whereNotNull($row->field)
                    ->whereJsonLength($row->field, '<>', 0);
            });
        })->when($keyword === '0' || $keyword === 'No', function ($query) use ($row) {
            $query->where(function ($query) use ($row) {
                $query
                    ->whereNull($row->field)
                    ->orWhereJsonLength($row->field, 0);
            });
        });
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
        $query->when($keyword === '1' || $keyword === 'Yes', function ($query) use ($row) {
            $query->whereNotNull($row->field);
        })->when($keyword === '0' || $keyword === 'No', function ($query) use ($row) {
            $query->whereNull($row->field);
        });
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
        $query->when($keyword === '1' || $keyword === 'Yes', function ($query) use ($row, $keyword) {
            $query->where(function ($query) use ($row, $keyword) {
                $query
                    ->whereNotNull($row->field)
                    ->whereJsonLength($row->field, '<>', 0);
            });
        })->when($keyword === '0' || $keyword === 'No', function ($query) use ($row, $keyword) {
            $query->where(function ($query) use ($row, $keyword) {
                $query
                    ->whereNull($row->field)
                    ->orWhereJsonLength($row->field, 0);
            });
        });
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

        $query->whereTranslation($row->field, 'LIKE', '%' . $keyword . '%');
    }

    /**
     * Filter by key
     *
     * @param Builder|QueryBuilder $query   Query
     * @param mixed                $keyword Keyword
     */
    protected function filterByKey(
        $query,
        $keyword,
        DataRow $row,
        DataType $dataType,
        Request $request
    ) {
        $model = app($dataType->model_name);
        switch ($model->getKeyType()) {
            case 'int':
                $query->whereKey((int) $keyword);
                break;
            case 'string':
                $query->whereKey($keyword);
                break;

            default:
                // code...
                break;
        }
    }
}
