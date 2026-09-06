<?php

use Illuminate\Support\Facades\Route;
use App\Livewire\CategoriesComponent;
use App\Livewire\AddCategoryComponent;
use App\Livewire\EditCategoryComponent;
use App\Livewire\ProductsComponent;
use App\Livewire\AddProductComponent;
use App\Livewire\EditProductComponent;
use App\Livewire\ProjectsComponent;
use App\Livewire\AddProjectComponent;
use App\Livewire\EditProjectComponent;
use App\Livewire\ArticlesComponent;
use App\Livewire\AddArticleComponent;
use App\Livewire\EditArticleComponent;
use App\Livewire\ServicesComponent;
use App\Livewire\AddServiceComponent;
use App\Livewire\EditServiceComponent;
use App\Livewire\SpecialOffersComponent;
use App\Livewire\AddSpecialOfferComponent;
use App\Livewire\EditSpecialOfferComponent;
use App\Livewire\SolutionCategoriesComponent;
use App\Livewire\AddSolutionCategoryComponent;
use App\Livewire\EditSolutionCategoryComponent;
use App\Livewire\SolutionsComponent;
use App\Livewire\AddSolutionComponent;
use App\Livewire\EditSolutionComponent;
use App\Livewire\BrandsComponent;
use App\Livewire\AddBrandComponent;
use App\Livewire\EditBrandComponent;
use App\Livewire\LicensesComponent;
use App\Livewire\AddLicenseComponent;
use App\Livewire\EditLicenseComponent;
use App\Livewire\PartnersComponent;
use App\Livewire\SliderComponent;
use App\Livewire\SettingsComponent;
use App\Livewire\ProfileComponent;




Route::redirect('/', 'login'); //Redirect to login anyway
Route::get('/register', function () {return redirect('/');}); //Close permission to register

Route::middleware(['auth:sanctum', 'verified'])->group(function(){

    // Приём картинок из Summernote: файл уходит сразу при вставке, в текст
    // подставляется ссылка (см. RichTextImageController).
    Route::post('/admin/rich-text/image', [\App\Http\Controllers\RichTextImageController::class, 'store'])
        ->name('admin.richtext.image');

    Route::view('/dashboard', 'dashboard')->name('dashboard');

    //Categories
    Route::get('/categories', CategoriesComponent::class)->name('categories'); //Get all categories
    Route::get('/category/add', AddCategoryComponent::class)->name('addcategory'); //Add new category
    Route::get('/category/edit/{category_slug}', EditCategoryComponent::class)->name('editcategory'); //Edit category

    //Products
    Route::get('/products', ProductsComponent::class)->name('products'); //Get all categories
    Route::get('/product/add', AddProductComponent::class)->name('addproduct'); //Add new category
    Route::get('/product/edit/{product_slug}', EditProductComponent::class)->name('editproduct'); //Edit category

    //Articles
    Route::get('/articles', ArticlesComponent::class)->name('articles'); //Get all articles
    Route::get('/article/add', AddArticleComponent::class)->name('addarticle'); //Add article
    Route::get('/article/edit/{article_slug}', EditArticleComponent::class)->name('editarticle'); //Edit article
    
    //Solution categories
    Route::get('/solcategories', SolutionCategoriesComponent::class)->name('solcategories'); //Get all solution categories
    Route::get('/solcategory/add', AddSolutionCategoryComponent::class)->name('addsolcategory'); //Add new solution category
    Route::get('/solcategory/edit/{solcategory_slug}', EditSolutionCategoryComponent::class)->name('editsolcategory'); //Edit solution category

    //Solutions
    Route::get('/solutions', SolutionsComponent::class)->name('solutions'); //Get all solutions
    Route::get('/solution/add', AddSolutionComponent::class)->name('addsolution'); //Add solution
    Route::get('/solution/edit/{solution_slug}', EditSolutionComponent::class)->name('editsolution'); //Edit solution

    //Projects
    Route::get('/projects', ProjectsComponent::class)->name('projects'); //Get all projects
    Route::get('/project/add', AddProjectComponent::class)->name('addproject'); //Add new project
    Route::get('/project/edit/{project_slug}', EditProjectComponent::class)->name('editproject'); //Edit project

    //Special Offers
    Route::get('/offers', SpecialOffersComponent::class)->name('offers'); //Get all offers
    Route::get('/offer/add', AddSpecialOfferComponent::class)->name('addoffer'); //Add new offer
    Route::get('/offer/edit/{offer_slug}', EditSpecialOfferComponent::class)->name('editoffer'); //Edit offer

    //Service
    Route::get('/services', ServicesComponent::class)->name('services'); //Get all services
    Route::get('/service/add', AddServiceComponent::class)->name('addservice'); //Add new service
    Route::get('/service/edit/{service_slug}', EditServiceComponent::class)->name('editservice'); //Edit service


    //Brands
    Route::get('/brands', BrandsComponent::class)->name('brands'); //Get all brands
    Route::get('/brand/add', AddBrandComponent::class)->name('addbrand'); //Add new brand
    Route::get('/brand/edit/{brand_slug}', EditBrandComponent::class)->name('editbrand'); //Edit brand    

    //License
    Route::get('/licenses', LicensesComponent::class)->name('licenses'); //Get all Licenses
    Route::get('/license/add', AddLicenseComponent::class)->name('addlicense'); //Add new license

    //Common pages
    Route::get('/profile', ProfileComponent::class)->name('profile');

    //Partners
    Route::get('/partners', PartnersComponent::class)->name('partners'); //Get all Licenses

    //Slider
    Route::get('/sliders', SliderComponent::class)->name('sliders'); //Get all Licenses


    //Settings
    Route::get('/settings', SettingsComponent::class)->name('settings'); //Get all Licenses
    
    Route::get('/redirects', \App\Livewire\RedirectsComponent::class)->name('redirects');
});

