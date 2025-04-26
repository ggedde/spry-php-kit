<?php declare(strict_types=1);
/**
 * Global Functions file
 */

namespace SpryPhp\Provider;

use Exception;

/**
 * Commands Class
 * Provides general commands for the CLI
 */
class Commands
{
    /**
     * Run a Command
     *
     * @throws Exception
     *
     * @return void
     */
    public static function runCommands(): void
    {
        $commands = [
            'model' => 'makeModel',
            'm' => 'makeModel',
            'controller' => 'makeController',
            'c' => 'makeController',
            'cm' => 'makeControllerAndModel',
            'mc' => 'makeControllerAndModel',
            'view' => 'makeView',
            'v' => 'makeView',
            'provider' => 'makeProvider',
            'p' => 'makeProvider',
            'type' => 'makeType',
            't' => 'makeType',
            // 'component' => 'makeComponent',
            // 'comp' => 'makeComponent',
            'help' => 'showHelp',
            'h' => 'showHelp',
        ];

        $command = isset($_SERVER['argv'][1]) && !empty($commands[$_SERVER['argv'][1]]) ? $commands[$_SERVER['argv'][1]] : null;

        if (!$command) {
            throw new Exception("\n\e[91mSpryPhp: Missing Command. Try spryphp help");
        }

        if (!is_callable([__CLASS__, $command])) {
            throw new Exception("\n\e[91mSpryPhp: Command is not Valid. Try spryphp help");
        }

        if ($command === 'showHelp') {
            self::showHelp();
            exit;
        }

        $value = isset($_SERVER['argv'][2]) ? Functions::formatCamelCase($_SERVER['argv'][2]) : null;
        $options = [];

        self::$command($value, $options);
    }

    /**
     * Make a Controller And Model
     *
     * @param string $name Name of Controller and Model. Controller Plural Name and Model gets Singular Name.
     *
     * @throws Exception
     *
     * @return void
     */
    private static function makeControllerAndModel(string $name): void
    {
        self::makeModel($name, true);
        self::makeController($name, $name);
    }

    /**
     * Make a Controller
     *
     * @param string $name      Name of Controller. Gets Plural Name.
     * @param string $modelName Name of Model. Gets Singular Name.
     *
     * @throws Exception
     *
     * @return void
     */
    private static function makeController(string $name, string $modelName = ''): void
    {
        $controllerName = ucwords(Functions::formatCamelCase(Functions::formatPlural($name)));

        if (!defined('APP_PATH_APP') || !is_dir(constant('APP_PATH_APP'))) {
            throw new Exception("\n\e[91mSpryPhp: Can't find App Directory");
        }

        if (!is_dir(constant('APP_PATH_APP').'/Controller')) {
            mkdir(constant('APP_PATH_APP').'/Controller');
        }

        if (!is_dir(constant('APP_PATH_APP').'/Controller')) {
            throw new Exception("\n\e[91mSpryPhp: Could Not Create Controller Directory");
        }

        if (file_exists(constant('APP_PATH_APP').'/Controller/'.$controllerName.'.php')) {
            throw new Exception("\n\e[91mSpryPhp: Controller already exists");
        }

        if ($modelName) {
            $modelName = ucwords(Functions::formatCamelCase(Functions::formatSingular($modelName)));
            $contents = '<?php declare(strict_types=1);
/**
 * This file is to handle The '.$controllerName.' Controller
 */

namespace App\Controller;

use App\Model\\'.$modelName.';
use SpryPhp\Provider\Db;

/**
 * '.$controllerName.' Controller
 */
class '.$controllerName.'
{
	/**
	 * Get '.$controllerName.' or single '.$modelName.' if WHERE has id.
	 *
	 * @param array $columns
	 * @param array $where
	 * @param array $order
	 * @param array $limit
	 *
	 * @return '.$modelName.'|'.$modelName.'[]
	 */
	public static function get(array $columns = [\'*\'], array $where = [], array $order = [], array $limit = []): '.$modelName.'|array
	{
		if (!empty($where[\'id\'])) {
			if ($item = new '.$modelName.'($where[\'id\'])) {
				return $item;
			}
		}

		$items = [];
		foreach (Db::select(\'submissions\', $columns, null, $where, [], $order, $limit) as $item) {
			$items[] = new '.$modelName.'($item);
		}

		return $items;
	}

	/**
	 * Add '.$modelName.' Function
	 *
	 * @param string $name
	 *
	 * @return '.$modelName.'|null
	 */
	public static function add(string $name): ?'.$modelName.'
	{
		$item = new '.$modelName.'(
			(object) [
				\'name\' => $name,
			]
		);

		return $item->insert() ? $item : null;
	}
}
';
        } else {
            $contents = '<?php declare(strict_types=1);
/**
 * This file is to handle The '.$controllerName.' Controller
 */

namespace App\Controller;

use SpryPhp\Provider\Db;

/**
 * '.$controllerName.' Controller
 */
class '.$controllerName.'
{
	/**
	 * Get '.$controllerName.'.
	 *
	 * @return array
	 */
	public static function get(): array
	{
		return [];
	}
}
';
        }

        file_put_contents(constant('APP_PATH_APP').'/Controller/'.$controllerName.'.php', $contents);
        echo "\n\e[92mController Created Successfully!\n";
    }

    /**
     * Make a Model
     *
     * @param string $name   Name of Model. Gets Singular Name.
     * @param string $withDb With DB Connection.
     *
     * @throws Exception
     *
     * @return void
     */
    private static function makeModel(string $name, $withDb = false): void
    {
        $modelName = ucwords(Functions::formatCamelCase(Functions::formatSingular($name)));

        if (!defined('APP_PATH_APP') || !is_dir(constant('APP_PATH_APP'))) {
            throw new Exception("\n\e[91mSpryPhp: Can't find App Directory");
        }

        if (!is_dir(constant('APP_PATH_APP').'/Model')) {
            mkdir(constant('APP_PATH_APP').'/Model');
        }

        if (!is_dir(constant('APP_PATH_APP').'/Model')) {
            throw new Exception("\n\e[91mSpryPhp: Could Not Create Model Directory");
        }

        if (file_exists(constant('APP_PATH_APP').'/Model/'.$modelName.'.php')) {
            throw new Exception("\n\e[91mSpryPhp: Model already exists");
        }

        $contents = '<?php declare(strict_types=1);
/**
 * This file is to handle The '.$modelName.' Class
 */

namespace App\Model;'.($withDb ? '

use SpryPhp\Model\DbItem;
' : '
').'
/**
 * '.$modelName.' Instance
 */
class '.$modelName.($withDb ? ' extends DbItem' : '').'
{
	/**
	 * '.$modelName.' Name
	 *
	 * @var string $name
	 */
	public string $name = \'\';
'.($withDb ? '
    /**
	 * Database Table
	 *
	 * @var string $dbTable
	 */
	protected string $dbTable = \''.strtolower($modelName).'\';
' : '').'
	/**
     * Construct the '.$modelName.'
     '.($withDb ? '* Either pass data as object or UUID.' : '*').'
     *
     * @param object|string $obj - Data Object'.($withDb ? ' or UUID as string' : '').'
     */
    public function __construct(object|string $obj)
    {'.($withDb ? '
        parent::__construct($obj);' : '
').'
    }
}
';

        file_put_contents(constant('APP_PATH_APP').'/Model/'.$modelName.'.php', $contents);
        echo "\n\e[92mModel Created Successfully!\n";
    }

    /**
     * Make a Model
     *
     * @param string $name Name of Model. Gets Singular Name.
     *
     * @throws Exception
     *
     * @return void
     */
    private static function makeView(string $name): void
    {
        $viewPathName = ucwords(Functions::formatCamelCase($name));
        $viewPathName = str_replace(' ', '/', ucwords(str_replace('/', ' ', $viewPathName)));

        $viewBaseName = ucwords(Functions::formatCamelCase(basename($name)));

        if (!defined('APP_PATH_APP') || !is_dir(constant('APP_PATH_APP'))) {
            throw new Exception("\n\e[91mSpryPhp: Can't find App Directory");
        }

        if (!is_dir(constant('APP_PATH_APP').'/View')) {
            mkdir(constant('APP_PATH_APP').'/View');
        }

        if (!is_dir(constant('APP_PATH_APP').'/View')) {
            throw new Exception("\n\e[91mSpryPhp: Could Not Create View Directory");
        }

        if (file_exists(constant('APP_PATH_APP').'/View/'.$viewPathName.'.php')) {
            throw new Exception("\n\e[91mSpryPhp: View already exists");
        }

        $contents = '<?php declare(strict_types=1);
/**
 * '.$viewBaseName.' View
 */

namespace App\View\\'.str_replace('/', '\\', $viewPathName).';

use SpryPhp\Model\View;

/**
 * Class for '.$viewBaseName.' View
 */
class '.$viewBaseName.' extends View
{
    /**
     * Render the '.$viewBaseName.' View
     */
    public function render()
    {
        ?>
            Hello World!
        <?php
    }
}
';

        file_put_contents(constant('APP_PATH_APP').'/View/'.$viewPathName.'.php', $contents);
        echo "\n\e[92mView Created Successfully!\n";
    }

    /**
     * Make a Type
     *
     * @param string $name Name of Type.
     *
     * @throws Exception
     *
     * @return void
     */
    private static function makeType(string $name): void
    {
        $typeName = ucwords(Functions::formatCamelCase($name));

        if (!defined('APP_PATH_APP') || !is_dir(constant('APP_PATH_APP'))) {
            throw new Exception("\n\e[91mSpryPhp: Can't find App Directory");
        }

        if (!is_dir(constant('APP_PATH_APP').'/Type')) {
            mkdir(constant('APP_PATH_APP').'/Type');
        }

        if (!is_dir(constant('APP_PATH_APP').'/Type')) {
            throw new Exception("\n\e[91mSpryPhp: Could Not Create Type Directory");
        }

        if (file_exists(constant('APP_PATH_APP').'/Type/'.$typeName.'.php')) {
            throw new Exception("\n\e[91mSpryPhp: Type already exists");
        }

        $contents = '<?php declare(strict_types = 1);
/**
 * This file is to handle '.$typeName.' Types
 */

namespace App\Type;

/**
 * '.$typeName.' Types
 */
enum '.$typeName.': string
{
    case TypeA = \'type-a\';
    case TypeB = \'type-b\';
}
';

        file_put_contents(constant('APP_PATH_APP').'/Type/'.$typeName.'.php', $contents);
        echo "\n\e[92mType Created Successfully!\n";
    }

    /**
     * Make a Provider
     *
     * @param string $name Name of Provider.
     *
     * @throws Exception
     *
     * @return void
     */
    private static function makeProvider(string $name): void
    {
        $providerName = ucwords(Functions::formatCamelCase($name));

        if (!defined('APP_PATH_APP') || !is_dir(constant('APP_PATH_APP'))) {
            throw new Exception("\n\e[91mSpryPhp: Can't find App Directory");
        }

        if (!is_dir(constant('APP_PATH_APP').'/Provider')) {
            mkdir(constant('APP_PATH_APP').'/Provider');
        }

        if (!is_dir(constant('APP_PATH_APP').'/Provider')) {
            throw new Exception("\n\e[91mSpryPhp: Could Not Create Provider Directory");
        }

        if (file_exists(constant('APP_PATH_APP').'/Provider/'.$providerName.'.php')) {
            throw new Exception("\n\e[91mSpryPhp: Provider already exists");
        }

        $contents = '<?php declare(strict_types = 1);
/**
 * This file is to handle the '.$providerName.' Provider
 */

namespace App\Provider;

/**
 * '.$providerName.' Provider Class
 */
class '.$providerName.'
{
    /**
     * Run a Function
     *
     * @return void
     */
    public static function func(): void
    {

    }
}
';

        file_put_contents(constant('APP_PATH_APP').'/Provider/'.$providerName.'.php', $contents);
        echo "\n\e[92mProvider Created Successfully!\n";
    }

    /**
     * Show Help
     *
     * @throws Exception
     *
     * @access private
     *
     * @return void
     */
    private static function showHelp(): void
    {
        echo "\n\e[90mSpryPhp Commands:\n\t
        controller | c [controllerName]\n\t
        model | m [modelName]\n\t
        mc [modelAndControllerName]\n\t
        view | v [viewName]\n\t
        type | t [typeName]\n\t
        provider | p [providerName]\n\t
        ";
    }
}
