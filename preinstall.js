/* eslint-disable flowtype/require-valid-file-annotation */

if (process.env.npm_execpath.indexOf('npm') === -1
    && process.env.npm_execpath.indexOf('pnpm') === -1 // @experimental supported
    && process.env.npm_execpath.indexOf('bun') === -1 // @experimental supported
) {
    throw new Error('\x1b[31mYou must use "npm install", yarn is not supported\x1b[0m');
}
