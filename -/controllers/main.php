<?php namespace std\controls\form\controllers;

class Main extends \Controller
{
    private $model;

    public function __create()
    {
        $model = $this->model = $this->unpackModel();

        $this->instance_(underscore_model($model));

        $this->dmap('|' . underscore_model($model), array_keys(unmap($this->data, 'model')));
    }

    public function reload()
    {
        $this->jquery('|')->replace($this->view());
    }

    public function view()
    {
        $v = $this->v('|');

        $model = $this->model;

        $fields = $this->getFields();

        foreach ($fields as $field => $fieldData) {
            if ($control = $fieldData['control'] ?? false) {
                $content = $this->_call($fieldData['control'])->perform();
            } else {
                $content = $model->{$field};
            }

            $label = '';
            if (!isset($fieldData['label_visible']) || $fieldData['label_visible']) {
                $label = $fieldData['label'] ?? $field;
            }

            $v->assign('field', [
                'FIELD'   => $field,
                'LABEL'   => $label,
                'CLASS'   => $field . ' ' . ($fieldData['class'] ?? ''),
                'CONTROL' => $content
            ]);

            if ($cellClickCall = $this->data('cells_click_calls/' . $field)) {
                $this->c('\std\ui button:bind', [
                    'selector' => $this->_selector('|') . " .row[field='" . $field . "']",
                    'path'     => $cellClickCall[0],
                    'data'     => $this->tokenizeData($model, $field, $cellClickCall[1] ?? [])
                ]);
            }
        }

        $this->css();

        return $v;
    }

    private function getFields()
    {
        $fields = handlers()->render($this->data('handlers/fields'));

        $output = [];

        foreach ($fields as $fieldId => $field) {
            $output[$fieldId] = $this->fixControl($fieldId, $field);
        }

        return $output;
    }

    private function fixControl($fieldId, $field)
    {
        if (!empty($field['control'])) {
            $fieldControl = &$field['control'];

            $controlsData = $this->getControlsData();

            if ($controlCall = ap($controlsData, $fieldControl[0])) {
                $controlPath = $controlCall['path'];
                $controlData = $controlCall['data'] ?? [];

                ra($controlData, $fieldControl[1] ?? []);

                $controlData = $this->tokenizeData($this->model, $fieldId, $controlData);

                $fieldControl[0] = $controlPath;
                $fieldControl[1] = $controlData;
            } else {
                $fieldControl[1] = $this->tokenizeData($this->model, $fieldId, $fieldControl[1]);
            }
        }

        return $field;
    }

    private function tokenizeData($model, $columnId, $data)
    {
        $flatten = a2f($data);

        foreach ($flatten as $path => $value) {
            if ($value === '%model') {
                $flatten[$path] = $model;
            } elseif ($value === '%model_id') {
                $flatten[$path] = $model->id;
            } elseif ($value === '%pack') {
                $flatten[$path] = pack_model($model);
            } elseif ($value === '%xpack') {
                $flatten[$path] = xpack_model($model);
            } elseif ($value === '%cell') {
                $flatten[$path] = pack_cell($model, $columnId); // будет работать только для полей в текущей таблице (не на связях)
            }

            if (null !== $columnId) {
                if ($value === '%column_id') {
                    $flatten[$path] = $columnId;
                } elseif ($value === '%value') {
                    $flatten[$path] = $model->{$columnId};
                }
            }
        }

        $output = f2a($flatten);

        return $output;
    }

    private $controlsData;

    private function getControlsData()
    {
        if (null === $this->controlsData) {
            $controlsHandler = $this->data('handlers/controls') ?: 'std/controls:';

            $this->controlsData = handlers()->render($controlsHandler);
        }

        return $this->controlsData;
    }
}
