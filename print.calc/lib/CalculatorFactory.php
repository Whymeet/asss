<?php

class CalculatorFactory {
    private $priceConfig;
    
    public function __construct($priceConfig) {
        $this->priceConfig = $priceConfig;
    }
    
    /**
     * Создает экземпляр калькулятора нужного типа
     * @param string $type Тип калькулятора
     * @return CalculatorInterface
     * @throws \InvalidArgumentException если тип неизвестен
     */
    public function createCalculator(string $type): CalculatorInterface {
        switch(strtolower($type)) {
            case 'list':
            case 'листовка':
                return new ListCalculator($this->priceConfig);
                
            case 'vizit':
            case 'визитка':
                return new VizitCalculator($this->priceConfig);
                
            case 'booklet':
            case 'буклет':
                return new BookletCalculator($this->priceConfig);
                
            case 'note':
            case 'блокнот':
                return new NoteCalculator($this->priceConfig);
                
            case 'catalog':
            case 'каталог':
                return new CatalogCalculator($this->priceConfig);
                
            case 'avtoviz':
            case 'автовизитка':
                return new AvtovizCalculator($this->priceConfig);
                
            case 'card':
            case 'открытка':
                return new CardCalculator($this->priceConfig);
                
            default:
                throw new \InvalidArgumentException("Неизвестный тип калькулятора: $type");
        }
    }
}
