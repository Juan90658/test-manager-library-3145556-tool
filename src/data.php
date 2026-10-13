<?php

declare(strict_types=1);

// Demo catalog (40 books across the five fixed categories) and 10 members.
// Category names must match the seeded categories exactly (data-model §9).

const BOOKS = [
    ['title' => 'Cien años de soledad', 'author' => 'Gabriel García Márquez', 'category' => 'Novela', 'copies' => 4],
    ['title' => 'El amor en los tiempos del cólera', 'author' => 'Gabriel García Márquez', 'category' => 'Novela', 'copies' => 2],
    ['title' => 'La vorágine', 'author' => 'José Eustasio Rivera', 'category' => 'Novela', 'copies' => 2],
    ['title' => 'Breve historia del tiempo', 'author' => 'Stephen Hawking', 'category' => 'Ciencia', 'copies' => 3],
    ['title' => 'Cosmos', 'author' => 'Carl Sagan', 'category' => 'Ciencia', 'copies' => 2],
    ['title' => 'El gen egoísta', 'author' => 'Richard Dawkins', 'category' => 'Ciencia', 'copies' => 2],
    ['title' => 'El origen de las especies', 'author' => 'Charles Darwin', 'category' => 'Ciencia', 'copies' => 2],
    ['title' => 'Código limpio', 'author' => 'Robert C. Martin', 'category' => 'Tecnología', 'copies' => 2],
    ['title' => 'El programador pragmático', 'author' => 'Andrew Hunt', 'category' => 'Tecnología', 'copies' => 2],
    ['title' => 'Diseño orientado al dominio', 'author' => 'Eric Evans', 'category' => 'Tecnología', 'copies' => 2],
    ['title' => 'Patrones de diseño', 'author' => 'Erich Gamma', 'category' => 'Tecnología', 'copies' => 2],
    ['title' => 'Refactorización', 'author' => 'Martin Fowler', 'category' => 'Tecnología', 'copies' => 2],
    ['title' => 'Sapiens', 'author' => 'Yuval Noah Harari', 'category' => 'Historia', 'copies' => 2],
    ['title' => 'Las venas abiertas de América Latina', 'author' => 'Eduardo Galeano', 'category' => 'Historia', 'copies' => 2],
    ['title' => 'Historia de Colombia', 'author' => 'Jorge Orlando Melo', 'category' => 'Historia', 'copies' => 2],
    ['title' => 'El Principito', 'author' => 'Antoine de Saint-Exupéry', 'category' => 'Infantil', 'copies' => 2],
    ['title' => 'Rin rin renacuajo', 'author' => 'Rafael Pombo', 'category' => 'Infantil', 'copies' => 2],
    ['title' => 'Donde viven los monstruos', 'author' => 'Maurice Sendak', 'category' => 'Infantil', 'copies' => 2],
    ['title' => 'Matilda', 'author' => 'Roald Dahl', 'category' => 'Infantil', 'copies' => 2],
    ['title' => 'María', 'author' => 'Jorge Isaacs', 'category' => 'Novela', 'copies' => 2],
    ['title' => 'Rayuela', 'author' => 'Julio Cortázar', 'category' => 'Novela', 'copies' => 2],
    ['title' => 'Pedro Páramo', 'author' => 'Juan Rulfo', 'category' => 'Novela', 'copies' => 2],
    ['title' => 'Ficciones', 'author' => 'Jorge Luis Borges', 'category' => 'Novela', 'copies' => 2],
    ['title' => 'La casa de los espíritus', 'author' => 'Isabel Allende', 'category' => 'Novela', 'copies' => 2],
    ['title' => 'Delirio', 'author' => 'Laura Restrepo', 'category' => 'Novela', 'copies' => 2],
    ['title' => 'Una breve historia de casi todo', 'author' => 'Bill Bryson', 'category' => 'Ciencia', 'copies' => 2],
    ['title' => 'El universo elegante', 'author' => 'Brian Greene', 'category' => 'Ciencia', 'copies' => 2],
    ['title' => 'Física de lo imposible', 'author' => 'Michio Kaku', 'category' => 'Ciencia', 'copies' => 2],
    ['title' => 'Arquitectura limpia', 'author' => 'Robert C. Martin', 'category' => 'Tecnología', 'copies' => 2],
    ['title' => 'Estructuras de datos y algoritmos', 'author' => 'Niklaus Wirth', 'category' => 'Tecnología', 'copies' => 2],
    ['title' => 'Introducción a los algoritmos', 'author' => 'Thomas H. Cormen', 'category' => 'Tecnología', 'copies' => 2],
    ['title' => 'Bases de datos relacionales', 'author' => 'C. J. Date', 'category' => 'Tecnología', 'copies' => 2],
    ['title' => 'Armas, gérmenes y acero', 'author' => 'Jared Diamond', 'category' => 'Historia', 'copies' => 2],
    ['title' => 'La Segunda Guerra Mundial', 'author' => 'Antony Beevor', 'category' => 'Historia', 'copies' => 2],
    ['title' => 'Bolívar', 'author' => 'Indalecio Liévano Aguirre', 'category' => 'Historia', 'copies' => 2],
    ['title' => 'Los mitos griegos', 'author' => 'Robert Graves', 'category' => 'Historia', 'copies' => 2],
    ['title' => 'Charlie y la fábrica de chocolate', 'author' => 'Roald Dahl', 'category' => 'Infantil', 'copies' => 2],
    ['title' => 'Alicia en el país de las maravillas', 'author' => 'Lewis Carroll', 'category' => 'Infantil', 'copies' => 2],
    ['title' => 'La oruga muy hambrienta', 'author' => 'Eric Carle', 'category' => 'Infantil', 'copies' => 2],
    ['title' => 'Platero y yo', 'author' => 'Juan Ramón Jiménez', 'category' => 'Infantil', 'copies' => 2],
];

const MEMBERS = [
    ['full_name' => 'Laura Pérez', 'document_number' => '1075200001'],
    ['full_name' => 'Andrés Rojas', 'document_number' => '1075200002'],
    ['full_name' => 'Camila Torres', 'document_number' => '1075200003'],
    ['full_name' => 'Santiago Gómez', 'document_number' => '1075200004'],
    ['full_name' => 'Valentina Díaz', 'document_number' => '1075200005'],
    ['full_name' => 'Juan David Castro', 'document_number' => '1075200006'],
    ['full_name' => 'Mariana López', 'document_number' => '1075200007'],
    ['full_name' => 'Felipe Herrera', 'document_number' => '1075200008'],
    ['full_name' => 'Daniela Vargas', 'document_number' => '1075200009'],
    ['full_name' => 'Sebastián Ramírez', 'document_number' => '1075200010'],
];
