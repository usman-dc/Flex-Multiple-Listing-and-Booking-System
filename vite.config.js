import { defineConfig } from 'vite';
import path from 'path';
import { fileURLToPath } from 'url';

const __dirname = path.dirname( fileURLToPath( import.meta.url ) );

export default defineConfig( {
	root: __dirname,
	build: {
		outDir: 'dist',
		emptyOutDir: true,
		rollupOptions: {
			input: {
				admin: path.resolve( __dirname, 'assets/src/admin.js' ),
				'admin-booking-types-quick-add': path.resolve( __dirname, 'assets/src/admin-booking-types-quick-add.js' ),
				public: path.resolve( __dirname, 'assets/src/public.js' ),
				'listing-metabox': path.resolve( __dirname, 'assets/src/listing-metabox.js' ),
				blocks: path.resolve( __dirname, 'assets/src/blocks.js' ),
			},
			output: {
				entryFileNames: '[name].js',
				assetFileNames: ( assetInfo ) =>
					assetInfo.name && assetInfo.name.endsWith( '.css' ) ? '[name][extname]' : 'assets/[name][extname]',
			},
		},
	},
	css: {
		preprocessorOptions: {
			scss: {
				quietDeps: true,
			},
		},
	},
} );
