<?php

namespace NovaBi\NovaDashboardManager\Nova;

use App\Nova\Situation;
use Laravel\Nova\Fields\Boolean;
use NovaBi\NovaDashboardManager\Nova\Dashboardables\BaseFilter;

use Laravel\Nova\Resource;
use Laravel\Nova\Fields\Number;

use NovaBi\NovaDashboardManager\Traits\LoadMorphablesTrait;

use Digitalazgroup\PlainText\PlainText;
use Eminiarts\Tabs\Tabs;
use Illuminate\Http\Request;
use Laravel\Nova\Fields\BelongsToMany;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Fields\Textarea;
use DigitalCreative\InlineMorphTo\InlineMorphTo;
use DigitalCreative\InlineMorphTo\HasInlineMorphToFields;
use NovaAttachMany\AttachMany;
use Pdmfc\NovaCards\Info;
use OptimistDigital\NovaSortable\Traits\HasSortableRows;


class DashboardConfiguration extends Resource
{
//    public static $displayInNavigation = false;

    use HasSortableRows;
    use HasInlineMorphToFields;
    use LoadMorphablesTrait;


//    use TabsOnEdit;

    // Use this Trait

    public static $defaultSortField = 'sort_order';

    public static $group = 'Databoard';

    /**
     * The model the resource corresponds to.
     *
     * @var  string
     */
    public static $model = \NovaBi\NovaDashboardManager\Models\Dashboard::class;

    /**
     * The single value that should be used to represent the resource when being displayed.
     *
     * @var  string
     */
    public static $title = 'name';

    /**
     * The columns that should be searched.
     *
     * @var  array
     */
    public static $search = [
        'name'
    ];

    /**
     * Get the displayable label of the resource.
     *
     * @return  string
     */
    public static function label()
    {
        return __('Databoard Configuration');
    }

    /**
     * Get the displayable singular label of the resource.
     *
     * @return  string
     */
    public static function singularLabel()
    {
        return __('Dashboards');
    }

    /**
     * Get the fields displayed by the resource.
     *
     * @param \Illuminate\Http\Request $request
     * @return  array
     */
    public function fields(Request $request)
    {

        $dashboardables = config('nova-dashboard-manager.dashboardables.resources');

        /*
         * todo: autoload from config('nova-dashboard-manager.dashboardables.paths')
        $dashboardables = $this->loadMorphables(config('nova-dashboard-manager.dashboardables'));
        $dashboardables = array_filter($dashboardables, function ($boardable) {
            return class_basename($boardable) != 'BaseBoard';
        });
        */

        $fields = [
            InlineMorphTo::make(__('Board Type'), 'dashboardable')
                ->types($dashboardables)->required()->hideFromIndex()
//                ->default(),
        ];

        return
            array_merge(
                [
                    Text::make(__('Name'), 'name'),
                    Textarea::make(__('Description'), 'description')
                        ->alwaysShow()
                        ->rows(3)
                        ->withMeta(['extraAttributes' => [
                            'placeholder' => __('Provide a short description for internal use')]
                        ])
                        ->help(
                            'Internal Description'
                        ),
                    Boolean::make(__('Edit-Mode'), 'DashboardEditable')->default('true')->help('Enable to edit dashboard'),
                ],
                $fields,
                [
                    Boolean::make(__('Expand Filter by Default'), 'ExpandFilterByDefault')->hideFromIndex(),
                    Boolean::make(__('Grid: Compact'), 'GridCompact')->default(true)->help(__('Automatically move items up if there is space available'))->hideFromIndex(),
                    Number::make(__('Number of columns'), 'GridNumberOfColumns')->default('6')->min(1)->max(12)->help(__('Number of Columns you can arrange widgets'))->hideFromIndex(),


                    PlainText::make(__('Databoard Type'), function () {
                        if (method_exists($this->dashboardable, 'label')) {
                            return $this->dashboardable->label();
                        }
                        return '';
                    }),


                    AttachMany::make(__('Filters'), 'datafilters', Datafilter::class)
                        ->rules('min:1')
                        ->showCounts()
                        ->help('Select a Filters to attach')->onlyOnForms(),

                    AttachMany::make(__('Widgets'), 'datawidgets', Datawidget::class)
                        ->rules('min:1')
                        ->showCounts()
                        ->help('Select a Widgets to attach')->onlyOnForms(),

                    Number::make(__('Data Widgets'), function () {
                        return $this->datawidgets->count();
                    })->onlyOnIndex(),

                    Number::make(__('Data Filters'), function () {
                        return $this->datafilters->count();
                    })->onlyOnIndex(),

                    (new Tabs('Relations', [
                        'Data Widgets' => [
                            BelongsToMany::make('datawidgets')
                                ->rules('required')

                        ],
                        'Data Filters' => [
                            BelongsToMany::make('datafilters')
                                ->rules('required')

                        ]
                    ]))->defaultSearch(true),

                ]
            );

    }

    /**
     * Get the cards available for the request.
     *
     * @param \Illuminate\Http\Request $request
     * @return  array
     */
    public function cards(Request $request)
    {
        $cards = [];
        if (\NovaBi\NovaDashboardManager\Models\Datawidget::count() == 0) {
            $cards[] =(new Info())->info(__('Please <a href="dashboard-widgets" class="text-primary dim no-underline">configure your first Widget</a>'))->asHtml();
        }
        if (\NovaBi\NovaDashboardManager\Models\Datafilter::count() == 0) {
            $cards[] =(new Info())->info(__('Please <a href="dashboard-filters" class="text-primary dim no-underline">configure your first Filter</a>'))->asHtml();
        }
        return $cards;
    }

    /**
     * Get the filters available for the resource.
     *
     * @param \Illuminate\Http\Request $request
     * @return  array
     */
    public function filters(Request $request)
    {
        return [];
    }

    /**
     * Get the lenses available for the resource.
     *
     * @param \Illuminate\Http\Request $request
     * @return  array
     */
    public function lenses(Request $request)
    {
        return [];
    }

    /**
     * Get the actions available for the resource.
     *
     * @param \Illuminate\Http\Request $request
     * @return  array
     */
    public function actions(Request $request)
    {
        return [];
    }
    /**
     * Get the URI key for the resource.
     *
     * @return string
     */
    public static function uriKey()
    {
        return 'dashboard-configuration';
    }

    public static function availableForNavigation(Request $request)
    {
        return (config('nova-dashboard-manager.showToolMenu') === false);
    }
}
