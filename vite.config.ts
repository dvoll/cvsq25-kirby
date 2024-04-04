/**
 * MIT License
 *
 * Copyright(c) 2023 Fork Unstable Media GmbH
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files(the "Software"), to deal
 *     in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and / or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 */

import { defineConfig } from 'vite';
import kirby from 'vite-plugin-kirby'

export default defineConfig(({ mode }) => ({
  base: mode === 'development' ? '/' : '/assets/dist/',

  build: {
    outDir: "public/assets/dist",
    copyPublicDir: false,
    assetsDir: '',
    rollupOptions: {
      input: ['src/main.ts']
    }
  },

  plugins: [kirby({
    watch: [
      './site/(templates|snippets|controllers|models|layouts)/**/*.php',
      './content/**/*',
    ],
  })],
}));
