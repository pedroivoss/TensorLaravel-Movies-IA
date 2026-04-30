/**
 * Components/MovieCard.jsx
 *
 * Card de filme exibido tanto na busca geral quanto nas recomendações.
 *
 * Funcionalidades:
 *   - Exibe título, ano, nota IMDB e gêneros
 *   - Se o usuário já avaliou o filme: mostra a nota atual em estrelas
 *   - Seletor de estrelas (1-5) + botão "Salvar" para avaliar / atualizar
 *   - Ao salvar, chama onRate(movieId, rating) que dispara POST /api/ratings
 */
import { useState } from 'react';
import { Button, Card, Rate, Space, Tag, Tooltip, Typography } from 'antd';
import { StarFilled, CheckCircleOutlined, LoadingOutlined } from '@ant-design/icons';

const { Text, Title } = Typography;

// Descrições dos tooltips do Rate (1-5 estrelas)
const RATE_LABELS = ['Odiei', 'Não gostei', 'Neutro', 'Gostei', 'Amei'];

// Cores por gênero para manter consistência visual entre busca e recomendações
const genreColor = (genre) => {
    const map = {
        Action: 'red', Adventure: 'orange', Animation: 'cyan', Biography: 'purple',
        Comedy: 'gold', Crime: 'volcano', Documentary: 'geekblue', Drama: 'blue',
        Family: 'green', Fantasy: 'magenta', History: 'lime', Horror: 'red',
        Music: 'cyan', Musical: 'pink', Mystery: 'purple', Romance: 'pink',
        'Sci-Fi': 'geekblue', Thriller: 'volcano', War: 'default', Western: 'orange',
    };
    return map[genre] ?? 'default';
};

/**
 * @param {{
 *   movie      : object   - dados do filme
 *   currentRating: number|null - nota atual do usuário (null = não avaliado)
 *   onRate     : function - (movieId, rating) => Promise
 *   highlight  : boolean  - destaca gêneros que coincidem com favoritos
 * }} props
 */
export default function MovieCard({ movie, currentRating, onRate, highlight = false }) {
    // Nota selecionada no seletor (começa na nota atual ou 3 = neutro)
    const [selectedRating, setSelectedRating] = useState(currentRating ?? 3);
    const [saving, setSaving] = useState(false);
    const [saved, setSaved] = useState(false);

    const genres = (movie.genre ?? '')
        .split(',')
        .map(g => g.trim())
        .filter(Boolean);

    const handleSave = async () => {
        setSaving(true);
        try {
            await onRate(movie.id, selectedRating);
            setSaved(true);
            // Reseta o ícone de "salvo" após 2s
            setTimeout(() => setSaved(false), 2000);
        } finally {
            setSaving(false);
        }
    };

    return (
        <Card
            size="small"
            style={{
                marginBottom: 8,
                borderLeft: highlight ? '3px solid #1677ff' : undefined,
            }}
            styles={{ body: { padding: '10px 12px' } }}
        >
            <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'flex-start', gap: 8 }}>

                {/* Coluna esquerda: título + metadados */}
                <div style={{ flex: 1, minWidth: 0 }}>
                    <Title level={5} style={{ margin: 0, fontSize: 13 }} ellipsis={{ tooltip: movie.name }}>
                        {movie.name}
                    </Title>

                    <Space size={4} wrap style={{ marginTop: 4 }}>
                        {/* Ano de lançamento */}
                        {movie.release_year && (
                            <Text type="secondary" style={{ fontSize: 11 }}>
                                {movie.release_year}
                            </Text>
                        )}

                        {/* Nota IMDB */}
                        {movie.rate && (
                            <Tooltip title="Nota IMDB">
                                <Tag
                                    icon={<StarFilled style={{ color: '#faad14' }} />}
                                    style={{ fontSize: 11, background: '#fffbe6', borderColor: '#ffe58f', margin: 0 }}
                                >
                                    {movie.rate.toFixed(1)}
                                </Tag>
                            </Tooltip>
                        )}

                        {/* Duração */}
                        {movie.duration && (
                            <Text type="secondary" style={{ fontSize: 11 }}>
                                {movie.duration} min
                            </Text>
                        )}
                    </Space>

                    {/* Tags de gênero */}
                    <div style={{ marginTop: 6, display: 'flex', flexWrap: 'wrap', gap: 3 }}>
                        {genres.map(genre => (
                            <Tag
                                key={genre}
                                color={genreColor(genre)}
                                style={{ fontSize: 10, padding: '0 5px', lineHeight: '16px', margin: 0 }}
                            >
                                {genre}
                            </Tag>
                        ))}
                    </div>
                </div>

                {/* Coluna direita: avaliação */}
                <div style={{ display: 'flex', flexDirection: 'column', alignItems: 'flex-end', gap: 6, flexShrink: 0 }}>
                    {/* Seletor de estrelas */}
                    <Tooltip title={RATE_LABELS[selectedRating - 1]}>
                        <Rate
                            count={5}
                            value={selectedRating}
                            onChange={setSelectedRating}
                            style={{ fontSize: 14 }}
                        />
                    </Tooltip>

                    {/* Botão salvar */}
                    <Button
                        type={currentRating ? 'default' : 'primary'}
                        size="small"
                        icon={saving
                            ? <LoadingOutlined />
                            : saved
                                ? <CheckCircleOutlined style={{ color: '#52c41a' }} />
                                : null
                        }
                        onClick={handleSave}
                        loading={saving}
                        style={{ fontSize: 11 }}
                    >
                        {currentRating
                            ? (saved ? 'Salvo!' : 'Atualizar')
                            : (saved ? 'Salvo!' : 'Marcar como assistido')}
                    </Button>
                </div>
            </div>
        </Card>
    );
}
