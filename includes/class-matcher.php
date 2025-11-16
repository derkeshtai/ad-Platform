<?php
/**
 * Motor de Matching Contextual
 * Relaciona anuncios con contenido mediante keywords
 */

if (!defined('ABSPATH')) {
    exit;
}

class Ad_Platform_Matcher {

    /**
     * Rankear anuncios por relevancia
     */
    public function rank_ads_by_relevance($ads, $keywords) {
        if (empty($ads) || empty($keywords)) {
            return $ads;
        }

        $keywords = $this->normalize_keywords($keywords);
        $scored_ads = array();

        foreach ($ads as $ad) {
            $score = $this->calculate_relevance_score($ad, $keywords);
            $scored_ads[] = array(
                'ad' => $ad,
                'score' => $score,
            );
        }

        // Ordenar por score descendente
        usort($scored_ads, function($a, $b) {
            return $b['score'] - $a['score'];
        });

        // Retornar solo los anuncios ordenados
        return array_map(function($item) {
            return $item['ad'];
        }, $scored_ads);
    }

    /**
     * Calcular score de relevancia
     */
    private function calculate_relevance_score($ad, $keywords) {
        $score = 0;

        // Obtener keywords del anuncio
        $ad_keywords = $this->get_ad_keywords($ad->ID);

        if (empty($ad_keywords)) {
            return 0;
        }

        // Calcular coincidencias
        foreach ($keywords as $keyword) {
            if (in_array($keyword, $ad_keywords)) {
                $score += 10; // Coincidencia exacta
            } else {
                // Coincidencia parcial
                foreach ($ad_keywords as $ad_keyword) {
                    if (stripos($ad_keyword, $keyword) !== false || stripos($keyword, $ad_keyword) !== false) {
                        $score += 5;
                    }
                }
            }
        }

        // Bonus por coincidencia en título
        $title = strtolower(get_the_title($ad->ID));
        foreach ($keywords as $keyword) {
            if (stripos($title, $keyword) !== false) {
                $score += 15;
            }
        }

        return $score;
    }

    /**
     * Obtener keywords del anuncio
     */
    private function get_ad_keywords($ad_id) {
        $keywords = array();

        // Keywords de taxonomía
        $terms = wp_get_post_terms($ad_id, 'adp_keyword');
        foreach ($terms as $term) {
            $keywords[] = strtolower($term->name);
        }

        // Keywords del contenido
        $content = get_post_field('post_content', $ad_id);
        if ($content) {
            $content_keywords = $this->extract_keywords_from_text($content);
            $keywords = array_merge($keywords, $content_keywords);
        }

        return array_unique($keywords);
    }

    /**
     * Normalizar keywords
     */
    private function normalize_keywords($keywords) {
        if (is_string($keywords)) {
            $keywords = explode(',', $keywords);
        }

        $normalized = array();
        foreach ($keywords as $keyword) {
            $keyword = trim(strtolower($keyword));
            if (!empty($keyword)) {
                $normalized[] = $keyword;
            }
        }

        return array_unique($normalized);
    }

    /**
     * Extraer keywords de texto
     */
    private function extract_keywords_from_text($text, $limit = 10) {
        // Limpiar HTML
        $text = wp_strip_all_tags($text);
        $text = strtolower($text);

        // Palabras comunes a ignorar (stopwords)
        $stopwords = array('el', 'la', 'de', 'que', 'y', 'a', 'en', 'un', 'ser', 'se', 'no', 'haber', 'por', 'con', 'su', 'para', 'como', 'estar', 'tener', 'le', 'lo', 'todo', 'pero', 'más', 'hacer', 'o', 'poder', 'decir', 'este', 'ir', 'otro', 'ese', 'la', 'si', 'me', 'ya', 'ver', 'porque', 'dar', 'cuando', 'él', 'muy', 'sin', 'vez', 'mucho', 'saber', 'qué', 'sobre', 'mi', 'alguno', 'mismo', 'yo', 'también', 'hasta', 'año', 'dos', 'querer', 'entre', 'así', 'primero', 'desde', 'grande', 'eso', 'ni', 'nos', 'llegar', 'pasar', 'tiempo', 'ella', 'sí', 'día', 'uno', 'bien', 'poco', 'deber', 'entonces', 'poner', 'cosa', 'tanto', 'hombre', 'parecer', 'nuestro', 'tan', 'donde', 'ahora', 'parte', 'después', 'vida', 'quedar', 'siempre', 'creer', 'hablar', 'llevar', 'dejar', 'nada', 'cada', 'seguir', 'menos', 'nuevo', 'the', 'be', 'to', 'of', 'and', 'a', 'in', 'that', 'have', 'i', 'it', 'for', 'not', 'on', 'with', 'he', 'as', 'you', 'do', 'at', 'this', 'but', 'his', 'by', 'from', 'they', 'we', 'say', 'her', 'she', 'or', 'an', 'will', 'my', 'one', 'all', 'would', 'there', 'their');

        // Extraer palabras
        preg_match_all('/\b[a-záéíóúñ]{4,}\b/u', $text, $matches);
        $words = $matches[0];

        // Filtrar stopwords
        $words = array_filter($words, function($word) use ($stopwords) {
            return !in_array($word, $stopwords);
        });

        // Contar frecuencia
        $word_count = array_count_values($words);
        arsort($word_count);

        // Retornar top keywords
        return array_keys(array_slice($word_count, 0, $limit));
    }

    /**
     * Extraer keywords de un post de WordPress
     */
    public function extract_keywords_from_post($post_id) {
        $keywords = array();

        // Título
        $title = get_the_title($post_id);
        $keywords = array_merge($keywords, $this->normalize_keywords($title));

        // Tags
        $tags = get_the_tags($post_id);
        if ($tags) {
            foreach ($tags as $tag) {
                $keywords[] = strtolower($tag->name);
            }
        }

        // Categorías
        $categories = get_the_category($post_id);
        if ($categories) {
            foreach ($categories as $category) {
                $keywords[] = strtolower($category->name);
            }
        }

        // Contenido
        $content = get_post_field('post_content', $post_id);
        if ($content) {
            $content_keywords = $this->extract_keywords_from_text($content, 15);
            $keywords = array_merge($keywords, $content_keywords);
        }

        return array_unique(array_filter($keywords));
    }
}
