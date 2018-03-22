<?php

namespace Plinth\Common;

class Info implements \JsonSerializable
{
	const	INFORMATION = 'information',
			SUCCESS		= 'success',
			WARNING		= 'warning',
			ERROR		= 'error';

	/**
	 * @var string
	 */
	private $_message;

	/**
	 * @var string
	 */
	private $_label;

	/**
	 * @var string
	 */
	private $_type;

	/**
	 * @var array
	 */
	private $_types = [
		'information'	=> [
			'img'       => 'information.png',
			'base64'    => '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'
		],
		'success'		=> [
			'img'       => 'success.png' ,
			'base64'    => 'iVBORw0KGgoAAAANSUhEUgAAACAAAAAgCAYAAABzenr0AAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAABulJREFUeNqcV1lsXNUZ/u4yi2ccjyfxEjuOx06sJG5CUiiygowxWFAViFHSUNKgqqAQUMsDlUjLIuAFQZ4ipL5UVQUPCKGmUaMmWDYgCBICnlDSkNhphMfLjJexPV5m7PFsd+v/n1mYcSZecq3/Xs+95/zf92/n/EfCOq/2l1DhqsFJyHjGkrA/916SCsTCNcnAh0vTeP/bv2JxPXqltb4/+Bp2KBW44LRjX8fPOtBc68P2qjpYZhqxdBSJVIQkimhyAWPhEGaiUYTmkjBN9CcjOPzVaQyTHutOCCgPv4u/ORx44Y+Pvoz2Pe1Y1MahIUKz0rAsE4ZpwDAMIgOktRTmFmcQmh1GcPYmgjMxTM4Bpo5/9L2KF0mfsRECtq7TCBzcfU/dW79+D0vwI24GMRG+hun5AcxFh5FMGsLtOS0Omw0e13ZUlO+EU63H0OQV3Bi/TnOAxThCfX+Bj0Zq6yFge+gdjD73yO/rD7cdw4J5FVML13H1x3NIaSZkOTNJKjHTyt5sqopaz0PkITuuDH+JydkUJmYx+ekraFpJYqUa28OnETzeeXTrY/c+jqQygv6R/2B0sh+KAshSaeBbiFgZMpWuFnhdv8D3/l4EOCRhTPW9gsZCEkpRzN/G3/c2Nz/wm/anYDkn8N/Bf2F85ibIIChyFnwdkquKhDYPkwK4s7aLwuBHLGGUN7Sj3v8VenMOyxGQ2k5id0UdPvjDr55HuVfHQOACxsP/y1u+kcsqKNGUFqV/UmjY3IZIfJAE91Q24uzEFczxGDnnes8OnP/l3Q+gfBMwvzSIwPT1DLi8PqtZaH0giwGdbno2DBLNjyT8UNQUdm3bi/oqoKoF5xkzT6DpflTZ7Wht3e5DWZmMH0bO3Rk4Iaao2Lr39qCj+Qy0AhIzsa/RWLUP1R4Jig2tzYSZI6A0d+LE7m3bRKynIzeQ1qnElDsAJ8sPtZ5Fo+cQ9m89hS1lPuERHqNZGpELobrCBy95eceDOIFMasFuc+HpzZvccDodmCICIok2EG9a9TKW7/kndm4+lv92dN9lQYwHsb6YNooqTyPcTvK/G08zNhNwyuQSj1uGy+lGNBEULltpobWG5d2tDP7bInKh2DeZsVl9SS0Eb/lWQYAxBTbdVB6sGwlRvylDKyIgksrKCP9vlQJny73F4EMLZ3Hx5hGRS7my1OhPVRwZv2dKRc1BIUEbi27oRbXOgJxIHY1n0L2rR7iZQYvAd5cG7/nxOOxqiUResRLLuXpNpJega3p+kIgt3ba4fNhfe0ok1qFdZ0FbADQSfnbvuj24I1tFa+WSnFuPU1pmZ1vpgaN7LucH7/QeI9CPENMhnreARwh8kMDZcuU2K2cpAsi6NJFaphjZiiaIRCq4Wry/w2v3WeK5EXAOm41060aKNqmfyIh0MJIYjCWB2eg4XGpdfj3nBLo4eEQoX+0S4H4Ct61iOYlTqcNCbArLhMWYjM0E9Ng0euL0ciY6hnK1Kb+W8x5gJ4WsfChamgS/F+C53XIVom7SHY4EBQHGZGwmkLzxCT5eWALCSwE4UCdcZWW9wBaxW0uRyIOrq1vOuji0rHuWMBiLMRmbCaQXApjWEhgORywE5vpRY+/M13sRieHjGImeF+D85N9rgecIVJHOIOlmDMZiTMZWs73a8sAn+JPtGHrKXQOoqWhCpdqCiOHPLWIZEvSjd/RJUR38XoCv4XYe61FaYKYd8E8MiD6RsRiTsXP9gLk4gXjdz9Fs2bBHN4Joqe6CIS0hZUXzxjAY17YqZXaRtcA5lzYpjfDKbbjs78PIlIG5CVy8dg4f0qeFIgL8I/AdrjZ04ClJNlzxdAAtW7oIJYUE5m89A6zhcnZRBVkuwId6qWVPYGYec5fexrO8TXD8V7ZkHArTfwmfVh/EYQtp1yI1Ets8bfDYfTR6DKZk5jvJUnWe23hU2mlq1C4oWi2B9wnwsWnMfv46umnEGMli1ugiAuyxNC//Q5fwWc1BHFlOGWVRaqMcchka3J0oU7aI448pxSk8Zn6XFIuMZINLboBXOQAv2qgdH6bG5juMhAy2fJ7AnyDdoyTsTn3VtpyEuxVf55/xJvWJj9cTbnWlhKpNPupoKKbldVRW9qLjjmGkaZEJIRwNilLjbOeEWwyh9+szeIeGBEhm12rLC0l4SbbWH8C+u57Eu3YXmriT4b2cRZGLJ/DyygsMC9d5Oo7R6//GG5M/oJ8+T2WTTtvQ0YzERUL2o9pdjdq9h9FduR2PqC4033LaI016HCORMXwxcAE9y2FR53QuEt1vfKNHs8LvtiwRN0kFlzVJWUFHXVjyCZJoNsmWs8DanR5OS3nEzm1UtouSSpS9ni2v9O0sXnn9X4ABAFUK+nNCM645AAAAAElFTkSuQmCC'
		],
		'warning'		=> [
			'img'       => 'warning.png' ,
			'base64'    => 'iVBORw0KGgoAAAANSUhEUgAAACAAAAAgCAYAAABzenr0AAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAABD1JREFUeNrkV01sVFUUPve9++bNtA7zl5ZOS9phWmmxFEtpUfkZUpWVJm5ZEI3EYJQEA5oY3dPEhMCCFWogEkKCDQuEsGmghIQFlOCiAQUiLQQoilK1Mul0Ou/5nXffg7bMMDNA6YKbnJk7956f757zvfPuCNu2aS6HRnM85hyA5A8hRNnAT37d9WGlQd/zj/tZ+ujNnoF9mFplI3gSDnz6dn3VwPbVtj2y1xGe89rzKoGx/rXqw3Udy4lun3CE57zGe7MNQOzbtOT1ynBoTbx9MdHQKaLhUxRfkiBe4z3WmU0Avpaair6mt1JEv/2EiueIcpDrfcRrvMc6swVAO/Z5x7ZQbdycFwVv7vwMCptKMOc13mOdcvyWA8AfDeg9Le90E109imojsCFdMZ013mMd1n3WAPT+rzoPJlYuI5G+SjQ+ikQj0wFNiYl5ZtTZYx3WZZtnBUDs/2TpylAk+F58aRLE61dp92m0amc3rdnVTZd/j2ANIIb7iXVYl21KIWQpAHyNMfNQ4o02oj8vuO1LOgBCAUHhCoEEaKoUPKDDumxTCiGLAdCOf7F8W2RBVTyyMEh09xIC+RQAXScDQQ0DmZZwo0uVBeiwLtuwbbEYxQD4I369p3Htq0S3ziEIAmgIpOmOGAjKInRvTSod6LIN2xYj5OMA6H1fdu6ubU+Sz/wXxLuHExrqpDg96ZqTASkZgO6uSaUDXbZhW/bxOEIWAiB2bWhZFHrJ3Fi/ogFpvQhNEE8z3Awo4eCG9DLgiaF0YcO27IN9FSKkLNTvOxuCvU2pZqL0TXS7SVV3z4eGRqRbOLBEIuyHGXhwHk3ZwJZ9ZMYHe7HYAZkoJQPaka0d78fqoq2Rl8NEYyMzUv9QpG7gy1D1n7bnlgK27IN9sc988fIBMKsrje8a1+KZHx3iy8I04jkidCd5ljBxATApJ3xqbaoO27AtfLAv9sm+H6k13wemXEiYeN8m22o2JlO1RH8NuRhnlI9/6jm68UfYiVEXvo+YeClZM8tsqztKbCFdO32brg3e2bvum/ObsJjLBwDEW9yyqjl6qesDNJ2xW0STVuFmJuHcZ6ntLD4mtQL3IVv1iWAdDfwwSGcu33tl64FffnXRTSOh0ZkI9ia6auDsP2wLVcu8zwgn06LU5jhpwqY9n41ScwKky4lC1y7HJ/vOZLLTCOlxQPy4pf3dUKyitaoNfT2bzku6ByLVd838akd8fn9hXY+U8Mm+OQbH8lLrHdGsjwQONaXmq+AOiYp38R0fjygORC2XdEVM4JtjpP/O8HsCvZ3GPQB4pZAMNOAkE5aqWTFvSH19MuumGG4sWcQGZdAEBRoM1mLliqkA9LFs7uLZ3Vdabct26TELA5EFQEzY9k2vPXtPwTzM0XMpVupF4ikGPyt4sdAw5B8PgJcSs9xb7RMM230C0swKmus/p+KF/3f8vwADAI/AMsoFsvSRAAAAAElFTkSuQmCC'
		],
		'error'			=> [
			'img'       => 'error.png' ,
			'base64'    => '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'
		]
	];
	
	/**
	 * Info constructor.
	 * @param string $mess
	 * @param string $type
	 * @param string $label
	 */
	public function __construct ($mess, $type = self::SUCCESS, $label = null)
	{
		$this->_message	= $mess;
		$this->_type	= $type;
		$this->_label   = $label;
	}
	
	/**
	 * @return string
	 */
	public function getMessage()
	{
		return $this->_message;
	}
	
	/**
	 * @return string
	 */
	public function getType()
	{
	    return $this->_type;
	}
	
	/**
	 * @return string
	 */
	public function getLabel()
	{
	    return $this->_label;
	}
	
	/**
	 * @param string $label
	 */
	public function setLabel($label)
	{
	    $this->_label = $label;
	}
	
	/**
	 * @return boolean
	 */
	public function hasLabel()
	{
	    return $this->_label !== null;
	}
	
	/**
	 * @return string
	 */
	public function getBase64Image()
	{
	    return "data:image/png;base64," . $this->_types[$this->_type]['base64'];
	}
	
	/**
	 * @return string
	 */
	public function getImage()
	{
		return __IMAGES . $this->_types[$this->_type]['img'];
	}

	/**
	 * @return array
	 */
	public function getArray()
	{
	    return [
	       'message' => $this->getMessage(),
	       'label' => $this->getLabel(),
	       'type' => $this->getType(),
	       'img' => $this->getBase64Image()
	    ];
	}

	/**
	 * @return array
	 */
	public function jsonSerialize()
	{
		return $this->getArray();
	}
}