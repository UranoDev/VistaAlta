<?php

declare(strict_types=1);

namespace App\Filament\Resources\Posts;

use App\Filament\Resources\Posts\Pages\CreatePost;
use App\Filament\Resources\Posts\Pages\EditPost;
use App\Filament\Resources\Posts\Pages\ListPosts;
use App\Models\Post;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;

/**
 * Alta, edición y borrado de los posts de Convivencia. Lo redacta la Mesa
 * Directiva y sale publicado al guardarlo: no hay borrador ni moderación de por
 * medio, igual que en Actividades.
 *
 * Es el único formulario del panel con un editor de formato. El contenido es
 * Markdown y las imágenes van **dentro** del texto, no en un campo aparte: así
 * quien escribe decide dónde queda cada foto respecto de lo que la explica, que
 * es lo que un campo de imagen suelto no puede resolver.
 */
class PostsResource extends Resource
{
    protected static ?string $model = Post::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedNewspaper;

    protected static ?string $slug = 'convivencia';

    protected static ?string $navigationLabel = 'Convivencia';

    protected static ?string $modelLabel = 'post';

    protected static ?string $pluralModelLabel = 'posts';

    protected static ?string $recordTitleAttribute = 'titulo';

    /**
     * Al final del menú del panel, después de la rendición de cuentas
     * (Comentarios 10, Actividades 40, Pendientes 45, Reporte financiero 50).
     * No es que importe menos: es que no rinde cuentas de nada, y el panel se
     * ordena por eso.
     */
    protected static ?int $navigationSort = 60;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()
                ->description('Lo que la Mesa Directiva publica sobre cómo se vive el fraccionamiento. Se lee en su propia página, con su propia dirección.')
                ->schema([
                    TextInput::make('titulo')
                        ->label('Título')
                        ->helperText('Encabeza la página del post y es el enlace que se ve en el índice.')
                        ->required()
                        ->maxLength(160)
                        /*
                         * El slug se sugiere del título mientras se captura, y
                         * solo al crear: en un post ya publicado, reescribir el
                         * título cambiaría la dirección sin avisar y dejaría en
                         * 404 el enlace que ya anda circulando. Ahí el slug se
                         * cambia a mano o no se cambia.
                         */
                        ->live(onBlur: true)
                        ->afterStateUpdated(function (string $operation, ?string $state, Set $set): void {
                            if ($operation === 'create') {
                                $set('slug', Str::slug((string) $state));
                            }
                        }),

                    TextInput::make('slug')
                        ->label('Dirección')
                        ->prefix(url('/convivencia').'/')
                        ->helperText('Se sugiere del título al crear el post y se puede cambiar. Ojo: cambiarla deja en 404 cualquier enlace de este post que ya se haya compartido.')
                        ->required()
                        ->maxLength(160)
                        ->unique(ignoreRecord: true)
                        // Minúsculas, dígitos y guiones simples. Es el mismo
                        // patrón que la ruta acepta (`routes/web.php`): un slug
                        // que no case ahí produciría un post inalcanzable.
                        ->rule('regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/')
                        ->validationMessages([
                            'regex' => 'La dirección va en minúsculas, con números y guiones: manejo-de-la-basura.',
                            'unique' => 'Ya hay otro post con esta dirección.',
                        ]),

                    DatePicker::make('publicado_en')
                        ->label('Fecha de publicación')
                        ->helperText('Es lo que ordena el índice, de lo más reciente hacia atrás.')
                        ->required()
                        ->native(false)
                        ->displayFormat('d/M/Y')
                        ->default(now()),

                    MarkdownEditor::make('contenido')
                        ->label('Contenido')
                        ->helperText('Arrastra una imagen al editor para subirla e insertarla donde esté el cursor. Se guarda con el post y se ve en la página pública.')
                        ->required()
                        /*
                         * El disco va dicho a fuerza. Sin `config/filament.php`
                         * publicado —y este proyecto no lo tiene—, el editor se
                         * queda sin disco al que subir. Es `public` porque la
                         * imagen de un post se ve sin sesión, como la página que
                         * la contiene: el disco `local` no se sirve por HTTP.
                         *
                         * Pide `php artisan storage:link` en el despliegue; sin
                         * el enlace, la imagen sube pero se pinta rota.
                         */
                        ->fileAttachmentsDisk('public')
                        ->fileAttachmentsDirectory('convivencia')
                        ->minHeight('24rem')
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('publicado_en')
                    ->label('Publicado')
                    ->date('d/M/Y')
                    ->sortable(),

                TextColumn::make('titulo')
                    ->label('Título')
                    ->wrap()
                    ->searchable(),

                TextColumn::make('slug')
                    ->label('Dirección')
                    ->prefix('/convivencia/')
                    ->color('gray')
                    ->searchable(),
            ])
            // El mismo orden que lee el Colono en el índice.
            ->defaultSort('publicado_en', 'desc')
            ->recordActions([
                // El atajo a la página pública: es la única pantalla del panel
                // cuyo resultado no se ve completo desde el propio panel, porque
                // el Markdown se pinta al final del otro lado.
                Action::make('ver')
                    ->label('Ver publicado')
                    ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                    ->url(fn (Post $record): string => $record->urlPublica())
                    ->openUrlInNewTab(),

                EditAction::make(),

                DeleteAction::make()
                    ->modalHeading('Borrar este post')
                    ->modalDescription('Desaparece del índice y su dirección queda en 404. No hay forma de recuperarlo.'),
            ])
            ->toolbarActions([
                DeleteBulkAction::make(),
            ])
            ->emptyStateHeading('Todavía no hay publicaciones')
            ->emptyStateDescription('Cada post que captures aparece de inmediato en la página de Convivencia, con su propia dirección.');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPosts::route('/'),
            'create' => CreatePost::route('/nuevo'),
            'edit' => EditPost::route('/{record}/editar'),
        ];
    }
}
