# Memoria Bate Ye
En esta práctica se ha desarrollado los diferentes endpoints para manejar los recursos de `Results`.


**NOTA: Sólamente se podrá acceder a los endpoints si se proporciona un token al usuario, sino, las operaciones devolverán: `401 UNAUTHORIZED`**

## Result
Para generar la entidad de `Result` se ha empleado el comando `make:entity` que ha simplificado mucho la creación de esta entidad.

Además ha creado un `ResultRepository` que es usado por el `ApiResultsQueryController` para manejar los datos en la base de datos.

## ApiREsultsQueryController
En esta clase se hann definido los endpoints de esta ruta, la ruta base es `/api/v1/results`, se han definido endpoints para las operaciones:\
`GET, POST, CGET, PUT, DELETE, OPTIONS`.

## GET y CGET
Para las operaciones `GET` y `CGET`, se hace un control de si el usuario está registrado, si no está registrado no puede ver ningún resultado.

Para los usuarios registrados:
1. Si son `ROLE_ADMIN`: pueden recuperar cualquier resultado, sea suyo o de otro usuario.
2. Si son `ROLE_USER` sin `ROLE_ADMIN`: estos usuarios sólamente podrán ver sus propios resultados, si intentan acceder a los resultados de otros usuarios (por `GET`), dará un error `403 FORBIDDEN`.

## POST
Todos los usuarios pueden crear resultados con `POST`, pero se tiene en cuenta que:
1. Sólamente los usuarios con `ROLE_ADMIN` pueden crear resultados para un usuario diferente a ellos, si un usuario con `ROLE_USER` intenta crear un resultado para otro usuario, dará un error `403 FORBIDDEN`.
2. Los campos `result` y `time` son obligatorios, si no se proporcionan dará un error `400 BAD REQUEST`, además `time` tiene que ser un string con el formato `'Y-m-d H:i:s'`, en caso contrario también dará un error `400 BAD REQUEST`.
3. El campo `user` no es obligatorio, si no le pasamos nada, usará el usuario loggeado, y si le pasamos un valor, chequeará si existe el usuario y en caso de que no exista, dará un error `404 NOT FOUND`.

## PUT
La mayoría de las restricciones aquí son las mismas que en `POST`, pero para `PUT` se debe proporcionar además del `resultId` del resultado a actualizar, 
un `Etag` que se puede obtener buscando el `resultId` anteriormente con `GET`.

## DELETE
Para borrar un resultado, al igual que `GET`, el usuario que **NO** sea `ROLE_ADMIN` sólamente podrá borrar sus resultados, si intenta borrar otros, dará un error `403 FORBIDDEN`.

Al borrar el resultado, la respuesta devuelta será un `204 NO CONTENT`.

## OPTIONS
Esta es la única operación que se puede usar sin loggear, las operaciones que se pueden realizar en una ruta son:
1. Para `/results`: `GET,POST,OPTIONS`
2. Para `/results/{resultId}`: `GET,PUT,DELETE,OPTIONS`


## api-doc
Para esta parte, he creado el esquema de un resultado, he creado un mensaje nuevo de error `400` para los resultados, he creado el parámetro para `resultId`, y también he creado un `RequestBody` para las operaciones
`PUT` y `POST`.

Además he creado todas las operaciones necesarias aquí con su título, descripción, parámetros, body y el tag `Results`.
