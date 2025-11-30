<?php

declare(strict_types=1);

namespace Tests\Integration\Filament\Support;

use App\Enum\ConfigTypeEnum;
use App\Filament\Support\ConfigFilamentMapper;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Tests\TestCase;

final class ConfigFilamentMapperTest extends TestCase
{
    public function testSelectableOptionsRenderAsSingleSelect(): void
    {
        $components = ConfigFilamentMapper::valueFormComponents(ConfigTypeEnum::STRING->value, ['one', 'two']);

        $this->assertCount(1, $components);

        $select = $components[0];

        $this->assertInstanceOf(Select::class, $select);
        $this->assertSame('value', $select->getName());
        $this->assertSame(['one' => 'one', 'two' => 'two'], $select->getOptions());
        $this->assertSame('Value', $select->getLabel());
        $this->assertTrue($select->isRequired());
        $this->assertFalse($select->isMultiple());
    }

    public function testJsonSelectAllowsMultipleValues(): void
    {
        $components = ConfigFilamentMapper::valueFormComponents(ConfigTypeEnum::JSON->value, ['alpha']);

        $this->assertCount(1, $components);

        $select = $components[0];

        $this->assertInstanceOf(Select::class, $select);
        $this->assertTrue($select->isMultiple());
    }

    public function testBooleanTypeMapsToRequiredToggle(): void
    {
        $components = ConfigFilamentMapper::valueFormComponents(ConfigTypeEnum::BOOL->value);

        $this->assertCount(1, $components);

        $toggle = $components[0];

        $this->assertInstanceOf(Toggle::class, $toggle);
        $this->assertSame('value', $toggle->getName());
        $this->assertSame('Value', $toggle->getLabel());
        $this->assertTrue($toggle->isRequired());
    }

    public function testNumericTypesReturnNumericTextInput(): void
    {
        foreach ([ConfigTypeEnum::INT, ConfigTypeEnum::FLOAT] as $type) {
            $components = ConfigFilamentMapper::valueFormComponents($type->value);

            $this->assertCount(1, $components);

            $textInput = $components[0];

            $this->assertInstanceOf(TextInput::class, $textInput);
            $this->assertSame('value', $textInput->getName());
            $this->assertSame('Value', $textInput->getLabel());
            $this->assertTrue($textInput->isRequired());
            $this->assertTrue($textInput->isNumeric());
        }
    }

    public function testJsonTypeReturnsRequiredKeyValueField(): void
    {
        $components = ConfigFilamentMapper::valueFormComponents(ConfigTypeEnum::JSON->value);

        $this->assertCount(1, $components);

        $keyValue = $components[0];

        $this->assertInstanceOf(KeyValue::class, $keyValue);
        $this->assertSame('value', $keyValue->getName());
        $this->assertSame('Value', $keyValue->getLabel());
        $this->assertTrue($keyValue->isRequired());
    }

    public function testStringTypeReturnsFullWidthTextarea(): void
    {
        $components = ConfigFilamentMapper::valueFormComponents(null);

        $this->assertCount(1, $components);

        $textarea = $components[0];

        $this->assertInstanceOf(Textarea::class, $textarea);
        $this->assertSame('value', $textarea->getName());
        $this->assertSame('Value', $textarea->getLabel());
        $this->assertTrue($textarea->isRequired());
        $this->assertSame('full', $textarea->getColumnSpan()['default']);
    }

    public function testTypeLabelUsesNormalizedCastType(): void
    {
        $this->assertSame('Json', ConfigFilamentMapper::typeLabel('ARRAY'));
        $this->assertSame('String', ConfigFilamentMapper::typeLabel(null));
    }
}
