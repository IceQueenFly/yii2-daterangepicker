<?php
namespace icequeen\yii\widgets;
use yii\base\Exception;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\web\JsExpression;
use yii\widgets\InputWidget;

class DateRangePicker extends InputWidget
{
    const ID_PREFIX = "default-id-";
    public $startAttribute;
    public $endAttribute;
    public $startName;
    public $endName;
    public $defaultInputOptions = [];
    public $endHideInputOptions = [];
    public $defaultId;
    private $startHideInputId;
    private $endHideInputId;
    public $value = "";
    public $format = 'Y-m-d H:i:s';
    public $separator = "~";
    public $isTimestamp = true;
    public $showRanges = false;// 快捷选择
    public $ranges = [];
    public $locale = [];
    public $pickerOptions = [];
    public $pickerEvents = [];
    public $containerTemplate = <<< HTML
     <div class="input-group"><span class="input-group-addon"><i class="glyphicon glyphicon-calendar"></i></span>{input}</div>
        
HTML;
    public $callback;


    public function init()
    {
        parent::init();
        if (!$this->hasModel()) {
            if (empty($this->endName)) {
                throw new Exception('配置endName');
            }
            if (!$this->startName) {
                $this->startName = $this->name;
            }
        } else {
            if (empty($this->endAttribute)) {
                throw new Exception('配置 model 必须配置 endAttribute');
            }
            if (!$this->startAttribute) {
                $this->startAttribute = $this->attribute;
            }
        }
        if (!isset($this->defaultId)) {
            $this->defaultId = $this->hasModel() ? static::ID_PREFIX . Html::getInputId($this->model, $this->attribute) : $this->getId();
            $this->startHideInputId = $this->hasModel() ? Html::getInputId($this->model, $this->startAttribute) : $this->getId() . '-' . $this->startName;
            $this->endHideInputId = $this->hasModel() ? Html::getInputId($this->model, $this->endAttribute) : $this->getId() . '-' . $this->endName;
        }
        $this->defaultInputOptions = ArrayHelper::merge(['id' => $this->defaultId], $this->defaultInputOptions);
        $this->initSetting();
        $this->getCallback();
        $this->registerAssets();
    }

    private function initSetting()
    {
        // 处理初始时间
        $startDate = date($this->format, time());
        $endDate = date($this->format, time());
        if ($this->hasModel()) {
            $start = $this->startAttribute;
            $end = $this->endAttribute;
            $startDate = $this->model->$start > 0 ? $this->changeTimestamp2Str($this->model->$start) : date($this->format, time());
            $endDate = $this->model->$end > 0 ? $this->changeTimestamp2Str($this->model->$end) : date($this->format, time());
            if ($this->model->$start && $this->model->$end) {
                $this->value = $startDate . $this->separator . $endDate;
            }
        }


        $_locale = [
            'format' => $this->format,
            "applyLabel" => "确定",
            "cancelLabel" => "取消",
            "resetLabel" => "重置",
            "customRangeLabel" => "自定义",
//            "daysOfWeek" => ['日', '一', '二', '三', '四', '五', '六'],
//            "monthNames" => ['一月', '二月', '三月', '四月', '五月', '六月', '七月', '八月', '九月', '十月', '十一月', '十二月'],
        ];
        $_pickerOptions = [
//            "timePicker" => true,
//            "timePicker24Hour" => true,
//            "timePickerSeconds" => true,
//            "linkedCalendars" => false,
            "autoUpdateInput" => false,
//            "showDropdowns" => true, //年月份下拉框
            "opens" => "center",
        ];
        $_ranges = [
            '今天' => [new JsExpression('moment()'), new JsExpression('moment()')],
            '昨天' => [new JsExpression("moment().subtract(1, 'days')"), new JsExpression("moment().subtract(1, 'days')")],
            '上周' => [new JsExpression("moment().subtract(6, 'days')"), new JsExpression('moment()')],
            '前30天' => [new JsExpression("moment().subtract(29, 'days')"), new JsExpression('moment()')],
            '本月' => [new JsExpression("moment().startOf('month')"), new JsExpression("moment().endOf('month')")],
            '上月' => [new JsExpression("moment().subtract(1, 'month').startOf('month')"), new JsExpression("moment().subtract(1, 'month').endOf('month')")]
        ];
        // DateRangePicker 配置
        $this->locale = ArrayHelper::merge($_locale, $this->locale);
        $this->pickerOptions = ArrayHelper::merge($_pickerOptions, $this->pickerOptions);
        $this->locale['separator'] = $this->separator;
        $this->pickerOptions['locale'] = ArrayHelper::merge($this->locale, (array)ArrayHelper::getValue($this->pickerOptions, 'locale'));
        $this->pickerOptions['startDate'] = $startDate;
        $this->pickerOptions['endDate'] = $endDate;
        $this->pickerOptions['ranges'] = count($this->ranges) == 0 ? (isset($this->pickerOptions['ranges']) ? $this->pickerOptions['ranges'] : $_ranges) : $this->ranges;
        if (!$this->showRanges) {
            unset($this->pickerOptions['ranges']);
        }
        if (isset($this->pickerOptions['locale']['format'])) {
            $this->pickerOptions['locale']['format'] = static::convertDateFormat(
                $this->pickerOptions['locale']['format']
            );
        }
        $this->pickerOptions = Json::encode($this->pickerOptions);
    }

    public function run()
    {
        if ($this->hasModel()) {
            $value = Html::activeInput('text', $this->model, $this->attribute, ArrayHelper::merge([
                'class' => 'form-control',
                'value' => $this->value,
            ], $this->defaultInputOptions));
            $this->containerTemplate = str_replace('{input}', $value, $this->containerTemplate);
            echo $this->containerTemplate;
            echo Html::activeHiddenInput($this->model, $this->startAttribute);
            echo Html::activeHiddenInput($this->model, $this->endAttribute);
        } else {
            $value = Html::input('text', $this->name, $this->value, $this->defaultInputOptions);
            $this->containerTemplate = str_replace('{input}', $value, $this->containerTemplate);
            echo $this->containerTemplate;
            echo Html::hiddenInput($this->startName, null, [
                'id' => $this->startHideInputId
            ]);
            echo Html::hiddenInput($this->endName, null, [
                'id' => $this->endHideInputId
            ]);
        }

    }

    protected function registerAssets()
    {
        $view = $this->getView();
        DateAsset::register($view);
        $js = <<<JS
            $('#{$this->defaultId}').daterangepicker(
                {$this->pickerOptions},
                {$this->callback}
            )
JS;
        foreach ($this->pickerEvents as $event => $handler) {
            $js .= ".on('$event', $handler)";
        }
        $view->registerJs($js);

    }

    private function getCallback()
    {
        if (empty($this->callback)) {
            $this->callback = <<<JS
            function(start, end, label) {
                if(!this.startDate){
                    this.element.val('');
                }else{
                   start = start.format(this.locale.format);
                   end = end.format(this.locale.format);
                    this.element.val(start + this.locale.separator + end);
                    if (parseInt('{$this->isTimestamp}')){
                        start=new Date(start);
                        end=new Date(end);
                        start=start.getTime()/1000;
                        end=end.getTime()/1000;                        
                    }
                    $("#{$this->startHideInputId}").val(start)
                    $("#{$this->endHideInputId}").val(end)
                }
            }
JS;
        }
    }

    // js时间格式和php时间格式转化
    protected static function convertDateFormat($format)
    {
        $conversions = [
            // meridian lowercase remains same
            // 'a' => 'a',
            // meridian uppercase remains same
            // 'A' => 'A',
            // second (with leading zeros)
            's' => 'ss',
            // minute (with leading zeros)
            'i' => 'mm',
            // hour in 12-hour format (no leading zeros)
            'g' => 'h',
            // hour in 12-hour format (with leading zeros)
            'h' => 'hh',
            // hour in 24-hour format (no leading zeros)
            'G' => 'H',
            // hour in 24-hour format (with leading zeros)
            'H' => 'HH',
            //  day of the week locale
            'w' => 'e',
            //  day of the week ISO
            'W' => 'E',
            // day of month (no leading zero)
            'j' => 'D',
            // day of month (two digit)
            'd' => 'DD',
            // day name short
            'D' => 'DDD',
            // day name long
            'l' => 'DDDD',
            // month of year (no leading zero)
            'n' => 'M',
            // month of year (two digit)
            'm' => 'MM',
            // month name short
            'M' => 'MMM',
            // month name long
            'F' => 'MMMM',
            // year (two digit)
            'y' => 'YY',
            // year (four digit)
            'Y' => 'YYYY',
            // unix timestamp
            'U' => 'X',
        ];
        return strtr($format, $conversions);
    }

    // 将时间戳转为字符串
    private function changeTimestamp2Str($timestamp)
    {
        if ($this->isTimestamp) {
            $timestamp = date($this->format, $timestamp);
        }
        return $timestamp;
    }
}