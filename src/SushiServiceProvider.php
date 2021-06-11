<?php

namespace Sushi;

use Illuminate\Support\ServiceProvider;

class SushiServiceProvider extends ServiceProvider {
  public function register() {
        $modelsDirectory = File::isDirectory(app_path('Models')) ? app_path('Models') : app_path();
        $hasModelsDirectory = $modelsDirectory === app_path('Models');

        $models = File::files($modelsDirectory);
    
        collect($models)
            ->map(function (SplFileInfo $file) use ($hasModelsDirectory) {
                $modelNamespace = $hasModelsDirectory ? "App\Models\\" : "App\\";
                $model = $modelNamespace . $file->getFilenameWithoutExtension();
                $uses = class_uses($model) ?: [];

                return !in_array("Sushi\Sushi", $uses) ? null : $model;
            })
            ->filter()
            ->each(function ($model) {
                $model::bootSushi();
            });
  }
}
