<?php
// Sécurité : accès uniquement via WP
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap ugm-docs">
    <h1><?php _e('Documentation – Block Binding (Gutenberg)', 'up-gutenberg-metabox'); ?></h1>
    <p><?php _e("Cette page explique comment lier (‘binder’) des blocs Gutenberg à des champs méta créés avec le plugin.", 'up-gutenberg-metabox'); ?></p>

    <hr/>

    <h2><?php _e("Démarrage rapide (core/post-meta)", 'up-gutenberg-metabox'); ?></h2>
    <ol>
        <li><?php _e("Créez un champ méta (ex: 'slug_meta') dans le plugin et activez 'Binding Gutenberg'.", 'up-gutenberg-metabox'); ?></li>
        <li><?php _e("Saisissez une valeur sur votre article/page.", 'up-gutenberg-metabox'); ?></li>
        <li><?php _e("Collez ce snippet dans l’éditeur (mode code).", 'up-gutenberg-metabox'); ?></li>
    </ol>
    <pre><code class="language-json">&lt;!-- wp:paragraph {"metadata":{"bindings":{"content":{"source":"core/post-meta","args":{"key":"slug_meta"}}}}} --&gt;
<p></p>
&lt;!-- /wp:paragraph --&gt;
</code></pre>

    <p><em><?php _e("Astuce: remplacez 'slug_meta' par la clé exacte de votre champ méta.", 'up-gutenberg-metabox'); ?></em></p>

    <h2><?php _e('Configurer un champ méta (1 min)', 'up-gutenberg-metabox'); ?></h2>
    <ol>
        <li><?php _e("Menu 'UG Metabox' > Configuration.", 'up-gutenberg-metabox'); ?></li>
        <li><?php _e("Ajouter/éditer un champ, cocher ‘Binding Gutenberg’, choisir le ‘Type REST’ si besoin.", 'up-gutenberg-metabox'); ?></li>
        <li><?php _e("Enregistrer.", 'up-gutenberg-metabox'); ?></li>
    </ol>
    <p><?php _e("Le plugin enregistre automatiquement la méta avec 'show_in_rest' pour l’exposer à l’éditeur et à l’API REST.", 'up-gutenberg-metabox'); ?></p>

    <h3><?php _e('Que fait le plugin ?', 'up-gutenberg-metabox'); ?></h3>
    <p><?php _e("Pour chaque champ avec Binding activé, le plugin appelle register_post_meta() avec un schéma REST adapté, sur tous les post types sélectionnés.", 'up-gutenberg-metabox'); ?></p>
    <pre><code>register_post_meta( $post_type, $meta_key, array(
    'single'       => true,
    'type'         => 'string|boolean|number|integer',
    'show_in_rest' => array(
        'schema' => array(
            'type' => 'string|boolean|number|integer',
        )
    ),
) );</code></pre>

    <hr/>

    <details class="ugm-adv"><summary><strong><?php _e('Avancé : exemples de blocs personnalisés', 'up-gutenberg-metabox'); ?></strong></summary>
    <h2><?php _e('2) Exemple: intégrer par CODE dans l’éditeur Gutenberg', 'up-gutenberg-metabox'); ?></h2>
    <p><?php _e("Objectif: afficher la valeur d’un champ méta (ex: 'ugm_subtitle') dans un bloc via un code custom. L’option de liaison directement depuis l’UI de l’éditeur peut ne pas être disponible selon votre version; utilisez donc l’approche code ci-dessous.", 'up-gutenberg-metabox'); ?></p>

    <h3><?php _e('Pré-requis', 'up-gutenberg-metabox'); ?></h3>
    <ul>
        <li><?php _e("Avoir un champ (ex: 'ugm_subtitle') créé via le plugin, avec Binding activé.", 'up-gutenberg-metabox'); ?></li>
        <li><?php _e("Avoir saisi une valeur pour ce champ sur un article/page.", 'up-gutenberg-metabox'); ?></li>
        <li><?php _e("Avoir un petit plugin ou un theme enfant pour ajouter un bloc custom.", 'up-gutenberg-metabox'); ?></li>
    </ul>

    <h3><?php _e("Option 1 – Un bloc personnalisé (source: meta)", 'up-gutenberg-metabox'); ?></h3>
    <p><?php _e("Créez un bloc dont un attribut lit directement une méta via 'source: meta'.", 'up-gutenberg-metabox'); ?></p>

    <p><strong>block.json</strong></p>
    <pre><code class="language-json">{
  "apiVersion": 2,
  "name": "example/ugm-subtitle",
  "title": "UGM Subtitle",
  "category": "text",
  "icon": "editor-paragraph",
  "supports": {
    "html": false
  },
  "attributes": {
    "content": {
      "type": "string",
      "source": "meta",
      "meta": "ugm_subtitle"
    }
  },
  "usesContext": ["postId", "postType"]
}</code></pre>

    <p><strong>edit.js</strong></p>
    <pre><code class="language-javascript">import { useBlockProps } from '@wordpress/block-editor';

export default function Edit({ attributes }) {
  const { content } = attributes;
  return (
    <p { ...useBlockProps() }>{ content || '(ugm_subtitle vide)' }</p>
  );
}
</code></pre>

    <p><?php _e("Grâce à 'source: meta' et au 'show_in_rest' géré par le plugin, l’attribut 'content' est synchronisé avec la méta 'ugm_subtitle'.", 'up-gutenberg-metabox'); ?></p>

    <h3><?php _e("Option 2 – Lire la méta via useEntityProp (sans 'source: meta')", 'up-gutenberg-metabox'); ?></h3>
    <p><?php _e("Alternative purement JS: lire les métas du post depuis l’éditeur et afficher la valeur souhaitée.", 'up-gutenberg-metabox'); ?></p>
    <pre><code class="language-javascript">import { useBlockProps } from '@wordpress/block-editor';
import { useSelect } from '@wordpress/data';
import { store as coreStore } from '@wordpress/core-data';

export default function Edit( { context } ) {
  const blockProps = useBlockProps();
  const postType = context.postType;
  const postId = context.postId;

  const meta = useSelect( ( select ) => {
    if ( ! postType || ! postId ) return {};
    const record = select( coreStore ).getEditedEntityRecord( 'postType', postType, postId );
    return record?.meta || {};
  }, [ postType, postId ] );

  const subtitle = meta?.ugm_subtitle || '';
  return <p { ...blockProps }>{ subtitle || '(ugm_subtitle vide)' }</p>;
}
</code></pre>

    <hr/>

    <h2><?php _e('3) Vérifier via l’API REST', 'up-gutenberg-metabox'); ?></h2>
    <p><?php _e("La méta exposée est disponible sur l’endpoint REST du post.", 'up-gutenberg-metabox'); ?></p>
    <pre><code class="language-bash"># Exemple (à adapter à votre site et post ID)
curl -X GET "https://votre-site.test/wp-json/wp/v2/posts/123" \
  -H "Accept: application/json"
</code></pre>
    <p><?php _e("Selon votre configuration, la méta peut apparaître dans le champ 'meta' ou être accessible via les mécanismes de Block Binding dans l’éditeur.", 'up-gutenberg-metabox'); ?></p>

    <hr/>

    <details class="ugm-adv"><summary><strong><?php _e('Avancé : Block Bindings via metadata.bindings (WP 6.5+)', 'up-gutenberg-metabox'); ?></strong></summary>
    <h2><?php _e('4) Block Bindings via metadata.bindings (avancé)', 'up-gutenberg-metabox'); ?></h2>
    <p><?php _e("Depuis WordPress 6.5+, vous pouvez lier des attributs de blocs via le champ 'metadata.bindings' directement dans la définition du bloc (comment JSON). Cela nécessite un 'binding source' enregistré côté serveur.", 'up-gutenberg-metabox'); ?></p>

    <h3><?php _e('4.1) Enregistrer une source de binding côté serveur (PHP)', 'up-gutenberg-metabox'); ?></h3>
    <p><?php _e("Exemple: une source qui renvoie une image aléatoire.", 'up-gutenberg-metabox'); ?></p>
    <pre><code class="language-php">add_action( 'init', function() {
    if ( ! function_exists( 'register_block_bindings_source' ) ) {
        return; // Requiert WP 6.5+
    }

    register_block_bindings_source( 'my-plugin/get-random-images', array(
        'label'              => __( 'Random image URL', 'up-gutenberg-metabox' ),
        'get_value_callback' => function( $source_args, $block_instance, $attribute_name ) {
            // Vous pouvez utiliser $block_instance->context['postId'] si besoin
            return 'https://picsum.photos/1000/600';
        },
        // 'uses_context' => array( 'postId', 'postType' ), // si vous consommez le contexte
    ) );
} );
</code></pre>

    <h3><?php _e('4.2) Utiliser cette source dans un bloc (comment JSON)', 'up-gutenberg-metabox'); ?></h3>
    <p><?php _e("Vous pouvez lier l’attribut 'url' d’un bloc Image à cette source.", 'up-gutenberg-metabox'); ?></p>
    <pre><code class="language-json">&lt;!-- wp:image {
    "metadata":{
        "bindings":{
            "url":{
                "source":"my-plugin/get-random-images"
            }
        }
    }
} /--&gt;
</code></pre>

    <h3><?php _e('4.3) Exemple: source générique pour lire une méta (args)', 'up-gutenberg-metabox'); ?></h3>
    <p><?php _e("Créez une source qui lit n’importe quelle méta du post via un argument 'key'.", 'up-gutenberg-metabox'); ?></p>
    <pre><code class="language-php">add_action( 'init', function() {
    if ( ! function_exists( 'register_block_bindings_source' ) ) {
        return;
    }
    register_block_bindings_source( 'ugm/meta', array(
        'label'              => __( 'UGM Meta', 'up-gutenberg-metabox' ),
        'get_value_callback' => function( $source_args, $block_instance, $attribute_name ) {
            $post_id = $block_instance->context['postId'] ?? 0;
            $key     = isset( $source_args['key'] ) ? sanitize_key( $source_args['key'] ) : '';
            if ( ! $post_id || ! $key ) {
                return '';
            }
            $value = get_post_meta( $post_id, $key, true );
            return is_scalar( $value ) ? (string) $value : '';
        },
        'uses_context' => array( 'postId' ),
    ) );
} );
</code></pre>

    <p><?php _e("Utilisation dans un bloc pour lier, par exemple, l’attribut 'content' d’un bloc Paragraphe à la méta 'ugm_subtitle':", 'up-gutenberg-metabox'); ?></p>
    <pre><code class="language-json">&lt;!-- wp:paragraph {
  "metadata": {
    "bindings": {
      "content": {
        "source": "ugm/meta",
        "args": { "key": "ugm_subtitle" }
      }
    }
  }
} --&gt;
<p></p>
&lt;!-- /wp:paragraph --&gt;
</code></pre>

    <p><em><?php _e("Astuce: combinez cette source avec les champs créés via le plugin (binding activé) pour centraliser vos données dans les métas.", 'up-gutenberg-metabox'); ?></em></p>

    <h3><?php _e("4.4) Utiliser la source intégrée core/post-meta (sans PHP)", 'up-gutenberg-metabox'); ?></h3>
    <p><?php _e("WordPress fournit une source de binding prête à l’emploi pour les métas: 'core/post-meta'. Il suffit d’indiquer la clé via 'args.key'.", 'up-gutenberg-metabox'); ?></p>
    <pre><code class="language-json">&lt;!-- wp:paragraph {"metadata":{"bindings":{"content":{"source":"core/post-meta","args":{"key":"slug_meta"}}}}} --&gt;
<p></p>
&lt;!-- /wp:paragraph --&gt;
</code></pre>
    </details>
    <hr/>

    <h2><?php _e('5) Conseils & bonnes pratiques', 'up-gutenberg-metabox'); ?></h2>
    <ul>
        <li><?php _e("Utilisez un 'binding_type' cohérent avec la nature de vos données (ex: boolean pour une case à cocher).", 'up-gutenberg-metabox'); ?></li>
        <li><?php _e("Préférez des clés méta en minuscules/sans espaces (ex: ugm_subtitle).", 'up-gutenberg-metabox'); ?></li>
        <li><?php _e("Pour les select, le plugin expose automatiquement la liste 'enum' si vous avez défini des options.", 'up-gutenberg-metabox'); ?></li>
        <li><?php _e("Testez toujours dans l’éditeur et via l’API REST pour valider votre configuration.", 'up-gutenberg-metabox'); ?></li>
    </ul>

    <p style="margin-top:20px;">
        <em><?php _e("Besoin d’un exemple plus avancé (bloc dynamique, formatage, etc.) ? Dites-nous ce que vous souhaitez réaliser et nous l’ajouterons ici.", 'up-gutenberg-metabox'); ?></em>
    </p>
</div>
