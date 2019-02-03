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
                'LABEL'   => $label,
                'CLASS'   => $field . ' ' . ($fieldData['class'] ?? ''),
                'CONTROL' => $content
            ]);
        }

        $this->css();

        return $v;
    }

    private function getFields()
    {
        $columns = handlers()->render($this->data('handlers/fields'));

        $output = [];

        foreach ($columns as $columnId => $column) {
            $output[$columnId] = $this->fixControl($columnId, $column);
        }

        return $output;
    }

    private function fixControl($columnId, $column)
    {
        if (!empty($column['control'])) {
            $control = &$column['control'];

            $controlsData = $this->getControlsData();

            if ($controlCall = ap($controlsData, $control[0])) {
                $control[0] = $controlCall['path'];
            }

            $controlData = $control[1] ?? [];

            ra($controlData, $controlCall['data'] ?? []);

            $control[1] = [
                'model' => $this->model,
                'field' => $columnId,
                'data'  => $controlData
            ];
        }

        return $column;
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
